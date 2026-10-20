<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class PreserveCaseHistoryWhenDeletingUsers extends Migration
{
    public $withinTransaction = false;

    private $snapshotColumns = [
        'disciplinario' => [
            'user_id_snapshot' => 'unsignedBigInteger',
            'user_name_snapshot' => 'string',
        ],
        'caso_evidencias' => [
            'user_id_snapshot' => 'unsignedBigInteger',
            'user_name_snapshot' => 'string',
        ],
        'caso_anexos' => [
            'user_id_snapshot' => 'unsignedBigInteger',
            'user_name_snapshot' => 'string',
        ],
        'user_permisos' => [
            'user_id_snapshot' => 'unsignedBigInteger',
            'user_name_snapshot' => 'string',
            'granted_by_id_snapshot' => 'unsignedBigInteger',
            'granted_by_name_snapshot' => 'string',
        ],
        'permiso_solicitudes' => [
            'user_id_snapshot' => 'unsignedBigInteger',
            'user_name_snapshot' => 'string',
            'responded_by_id_snapshot' => 'unsignedBigInteger',
            'responded_by_name_snapshot' => 'string',
        ],
        'sipd_avisos' => [
            'user_id_snapshot' => 'unsignedBigInteger',
            'user_name_snapshot' => 'string',
            'remitente_id_snapshot' => 'unsignedBigInteger',
            'remitente_name_snapshot' => 'string',
        ],
        'sipd_alerta_silencios' => [
            'user_id_snapshot' => 'unsignedBigInteger',
            'user_name_snapshot' => 'string',
        ],
        'sipd_recuperaciones_contrasena' => [
            'user_id_snapshot' => 'unsignedBigInteger',
            'user_name_snapshot' => 'string',
            'responded_by_id_snapshot' => 'unsignedBigInteger',
            'responded_by_name_snapshot' => 'string',
        ],
    ];

    private $userForeignKeyTables = [
        'disciplinario',
        'user_permisos',
        'permiso_solicitudes',
        'sipd_avisos',
        'sipd_alerta_silencios',
        'sipd_recuperaciones_contrasena',
    ];

    public function up()
    {
        $driver = DB::getDriverName();
        if (!in_array($driver, ['mysql', 'sqlite'], true)) {
            throw new RuntimeException('La preservación del historial solo está preparada para MySQL y SQLite.');
        }

        if ($driver === 'sqlite') {
            $originalForeignKeys = DB::selectOne('PRAGMA foreign_keys');
            if (!$originalForeignKeys || !property_exists($originalForeignKeys, 'foreign_keys')) {
                throw new RuntimeException('No se pudo determinar el estado de las claves foráneas de SQLite.');
            }
            DB::statement('PRAGMA foreign_keys = OFF');
            $transactionStarted = false;

            try {
                DB::beginTransaction();
                $transactionStarted = true;
                $this->addSnapshotColumns();
                foreach ($this->userForeignKeyTables as $table) {
                    $this->rebuildSqliteTableWithNullableUser($table);
                }

                $this->createAssignmentHistoryTable();
                $this->seedExistingAssignments();

                $violations = DB::select('PRAGMA foreign_key_check');
                if ($violations !== []) {
                    throw new RuntimeException('La migración detectó referencias foráneas inconsistentes.');
                }

                DB::commit();
                $transactionStarted = false;
            } catch (Throwable $exception) {
                if ($transactionStarted && DB::transactionLevel() > 0) {
                    DB::rollBack();
                }

                throw $exception;
            } finally {
                DB::statement(
                    'PRAGMA foreign_keys = ' .
                    ((int) $originalForeignKeys->foreign_keys === 1 ? 'ON' : 'OFF')
                );
            }

            return;
        }

        $this->addSnapshotColumns();

        foreach ($this->userForeignKeyTables as $tableName) {
            $this->preserveMysqlUserForeignKey($tableName);
        }

        $this->createAssignmentHistoryTable();
        $this->seedExistingAssignments();
    }

    public function down()
    {
        throw new RuntimeException(
            'Esta migración es irreversible tras eliminar usuarios: los valores de usuario anulados no pueden reconstruirse.'
        );
    }

    private function addSnapshotColumns(): void
    {
        foreach ($this->snapshotColumns as $tableName => $columns) {
            $missing = array_filter($columns, function ($type, $column) use ($tableName) {
                return !Schema::hasColumn($tableName, $column);
            }, ARRAY_FILTER_USE_BOTH);

            if ($missing === []) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($missing) {
                foreach ($missing as $column => $type) {
                    $table->{$type}($column)->nullable();
                }
            });
        }
    }

    private function preserveMysqlUserForeignKey(string $tableName): void
    {
        $column = DB::selectOne(
            'SELECT COLUMN_TYPE AS column_type, IS_NULLABLE AS is_nullable ' .
            'FROM information_schema.COLUMNS ' .
            'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND COLUMN_NAME = ?',
            [$tableName, 'user_id']
        );

        if (
            !$column
            || !preg_match('/^bigint(?:\(\d+\))? unsigned$/i', trim($column->column_type))
        ) {
            throw new RuntimeException('La columna de usuario no tiene la definición esperada.');
        }

        $foreignKeys = DB::select(
            'SELECT kcu.CONSTRAINT_NAME AS constraint_name, ' .
            'kcu.REFERENCED_TABLE_NAME AS referenced_table_name, ' .
            'rc.DELETE_RULE AS delete_rule, rc.UPDATE_RULE AS update_rule ' .
            'FROM information_schema.KEY_COLUMN_USAGE AS kcu ' .
            'INNER JOIN information_schema.REFERENTIAL_CONSTRAINTS AS rc ' .
            'ON rc.CONSTRAINT_SCHEMA = kcu.CONSTRAINT_SCHEMA ' .
            'AND rc.TABLE_NAME = kcu.TABLE_NAME ' .
            'AND rc.CONSTRAINT_NAME = kcu.CONSTRAINT_NAME ' .
            'WHERE kcu.CONSTRAINT_SCHEMA = DATABASE() ' .
            'AND kcu.TABLE_NAME = ? AND kcu.COLUMN_NAME = ? ' .
            'AND kcu.REFERENCED_TABLE_NAME IS NOT NULL',
            [$tableName, 'user_id']
        );

        if (count($foreignKeys) > 1) {
            throw new RuntimeException('Se encontraron varias claves foráneas para la atribución del usuario.');
        }

        $foreignKey = $foreignKeys[0] ?? null;
        if ($foreignKey && $foreignKey->referenced_table_name !== 'users') {
            throw new RuntimeException('La clave foránea de usuario apunta a una tabla inesperada.');
        }
        $updateRule = $foreignKey
            ? strtoupper((string) $foreignKey->update_rule)
            : 'RESTRICT';
        if (!in_array($updateRule, ['CASCADE', 'SET NULL', 'RESTRICT', 'NO ACTION'], true)) {
            throw new RuntimeException('La acción ON UPDATE de la clave de usuario no es compatible.');
        }
        if (
            $foreignKey
            && strtolower($foreignKey->delete_rule) === 'set null'
            && strtoupper($column->is_nullable) === 'YES'
        ) {
            return;
        }

        $quotedTable = '`' . $tableName . '`';
        $actions = [];
        if ($foreignKey) {
            if (!preg_match('/^[A-Za-z0-9_$]+$/', $foreignKey->constraint_name)) {
                throw new RuntimeException('El nombre de una clave foránea no se pudo validar.');
            }
            $actions[] = 'DROP FOREIGN KEY `' . $foreignKey->constraint_name . '`';
        }
        if (strtoupper($column->is_nullable) !== 'YES') {
            $actions[] = 'MODIFY COLUMN `user_id` BIGINT UNSIGNED NULL';
        }
        $actions[] = 'ADD CONSTRAINT `' . $tableName . '_user_id_foreign` ' .
            'FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ' .
            'ON DELETE SET NULL ON UPDATE ' . $updateRule;

        DB::statement('ALTER TABLE ' . $quotedTable . ' ' . implode(', ', $actions));
    }

    private function createAssignmentHistoryTable(): void
    {
        if (Schema::hasTable('disciplinario_asignaciones')) {
            foreach ([
                'caso_id',
                'user_id',
                'user_id_snapshot',
                'user_name_snapshot',
                'assigned_at',
                'unassigned_at',
            ] as $column) {
                if (!Schema::hasColumn('disciplinario_asignaciones', $column)) {
                    throw new RuntimeException('La tabla de asignaciones existente tiene un esquema inesperado.');
                }
            }

            if (!Schema::hasColumn('disciplinario_asignaciones', 'active_case_id')) {
                Schema::table('disciplinario_asignaciones', function (Blueprint $table) {
                    $table->unsignedBigInteger('active_case_id')->nullable();
                });
            }
        } else {
            Schema::create('disciplinario_asignaciones', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('caso_id');
                $table->foreign('caso_id')
                    ->references('id')
                    ->on('disciplinario')
                    ->onDelete('cascade');
                $table->unsignedBigInteger('user_id')->nullable();
                $table->foreign('user_id')
                    ->references('id')
                    ->on('users')
                    ->nullOnDelete();
                $table->unsignedBigInteger('active_case_id')->nullable();
                $table->unsignedBigInteger('user_id_snapshot')->nullable();
                $table->string('user_name_snapshot')->nullable();
                $table->timestamp('assigned_at')->nullable();
                $table->timestamp('unassigned_at')->nullable();
                $table->timestamps();
                $table->index(['caso_id', 'unassigned_at'], 'disciplinario_asignaciones_active_index');
            });
        }

        $this->ensureSingleActiveAssignmentConstraint();
    }

    private function ensureSingleActiveAssignmentConstraint(): void
    {
        $duplicates = DB::table('disciplinario_asignaciones')
            ->select('caso_id')
            ->whereNull('unassigned_at')
            ->groupBy('caso_id')
            ->havingRaw('COUNT(*) > 1')
            ->exists();

        if ($duplicates) {
            throw new RuntimeException('Hay expedientes con varias asignaciones activas; se requiere corregirlos manualmente.');
        }

        DB::table('disciplinario_asignaciones')
            ->whereNotNull('unassigned_at')
            ->whereNotNull('active_case_id')
            ->update(['active_case_id' => null]);

        DB::table('disciplinario_asignaciones')
            ->whereNull('unassigned_at')
            ->whereNull('active_case_id')
            ->update(['active_case_id' => DB::raw('caso_id')]);

        $invalidActiveIds = DB::table('disciplinario_asignaciones')
            ->whereNull('unassigned_at')
            ->whereColumn('active_case_id', '<>', 'caso_id')
            ->exists();

        if ($invalidActiveIds) {
            throw new RuntimeException('Una asignación activa contiene una referencia de expediente inconsistente.');
        }

        $indexName = 'disciplinario_asignaciones_active_case_unique';
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select('PRAGMA index_list("disciplinario_asignaciones")');
            foreach ($indexes as $index) {
                if ($index->name === $indexName) {
                    if ((int) $index->unique !== 1) {
                        throw new RuntimeException('El índice de asignación activa existente no es único.');
                    }

                    $indexColumns = DB::select(
                        'PRAGMA index_info("' . str_replace('"', '""', $indexName) . '")'
                    );
                    if (count($indexColumns) !== 1 || $indexColumns[0]->name !== 'active_case_id') {
                        throw new RuntimeException('El índice único de asignación activa tiene columnas inesperadas.');
                    }

                    return;
                }
            }
        } else {
            $indexes = DB::select(
                'SELECT NON_UNIQUE AS non_unique, COLUMN_NAME AS column_name, ' .
                'SEQ_IN_INDEX AS seq_in_index ' .
                'FROM information_schema.STATISTICS ' .
                'WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? AND INDEX_NAME = ?',
                ['disciplinario_asignaciones', $indexName]
            );
            if ($indexes !== []) {
                if (
                    (int) $indexes[0]->non_unique !== 0
                    || count($indexes) !== 1
                    || $indexes[0]->column_name !== 'active_case_id'
                    || (int) $indexes[0]->seq_in_index !== 1
                ) {
                    throw new RuntimeException('El índice de asignación activa existente no es único.');
                }

                return;
            }
        }

        $quotedIndex = DB::getDriverName() === 'sqlite'
            ? '"' . $indexName . '"'
            : '`' . $indexName . '`';
        $quotedTable = DB::getDriverName() === 'sqlite'
            ? '"disciplinario_asignaciones"'
            : '`disciplinario_asignaciones`';
        $quotedColumn = DB::getDriverName() === 'sqlite'
            ? '"active_case_id"'
            : '`active_case_id`';
        DB::statement('CREATE UNIQUE INDEX ' . $quotedIndex . ' ON ' . $quotedTable . ' (' . $quotedColumn . ')');
    }

    private function seedExistingAssignments(): void
    {
        DB::table('disciplinario')
            ->whereNotNull('user_id')
            ->select('id')
            ->orderBy('id')
            ->chunkById(500, function ($cases) {
                foreach ($cases as $candidate) {
                    DB::transaction(function () use ($candidate) {
                        $case = DB::table('disciplinario')
                            ->where('id', $candidate->id)
                            ->lockForUpdate()
                            ->first();

                        if (!$case || !$case->user_id) {
                            return;
                        }

                        $hasActiveAssignment = DB::table('disciplinario_asignaciones')
                            ->where('active_case_id', $case->id)
                            ->exists();
                        if ($hasActiveAssignment) {
                            return;
                        }

                        $user = DB::table('users')->where('id', $case->user_id)->first();
                        if (!$user) {
                            return;
                        }

                        DB::table('disciplinario_asignaciones')->insert([
                            'caso_id' => $case->id,
                            'user_id' => $case->user_id,
                            'active_case_id' => $case->id,
                            'user_id_snapshot' => $case->user_id,
                            'user_name_snapshot' => $user->name,
                            'assigned_at' => null,
                            'unassigned_at' => null,
                            'created_at' => now(),
                            'updated_at' => now(),
                        ]);
                    });
                }
            });
    }

    private function rebuildSqliteTableWithNullableUser(string $tableName): void
    {
        $temporaryTable = $tableName . '__preserve_history';
        if (Schema::hasTable($temporaryTable)) {
            throw new RuntimeException('Existe una tabla temporal de migración; se requiere revisión manual.');
        }

        $row = DB::selectOne(
            'SELECT sql FROM sqlite_master WHERE type = ? AND name = ?',
            ['table', $tableName]
        );
        if (!$row || !is_string($row->sql)) {
            throw new RuntimeException('No se encontró la definición esperada de una tabla histórica.');
        }

        $foreignKeysBefore = $this->sqliteForeignKeyDefinitions($tableName);
        $userForeignKeys = array_values(array_filter($foreignKeysBefore, function ($foreignKey) {
            return $foreignKey['from'] === 'user_id';
        }));
        $columns = DB::select('PRAGMA table_info("' . str_replace('"', '""', $tableName) . '")');
        $userColumns = array_values(array_filter($columns, function ($column) {
            return $column->name === 'user_id';
        }));

        if (
            count($userForeignKeys) !== 1
            || $userForeignKeys[0]['table'] !== 'users'
            || $userForeignKeys[0]['to'] !== 'id'
            || $userForeignKeys[0]['on_update'] !== 'NO ACTION'
            || $userForeignKeys[0]['match'] !== 'NONE'
            || count($userColumns) !== 1
        ) {
            throw new RuntimeException('La clave foránea de usuario no tiene la definición esperada.');
        }

        $userNullable = (int) $userColumns[0]->notnull === 0;
        if ($userForeignKeys[0]['on_delete'] === 'SET NULL') {
            if (!$userNullable) {
                throw new RuntimeException('La clave SET NULL requiere una columna user_id nullable.');
            }

            return;
        }
        if ($userForeignKeys[0]['on_delete'] !== 'CASCADE') {
            throw new RuntimeException('La acción ON DELETE de la clave de usuario no es compatible.');
        }

        $definition = preg_replace(
            '/^(CREATE TABLE\s+)(?:"|`|\[)?' . preg_quote($tableName, '/') . '(?:"|`|\])?/i',
            '$1"' . $temporaryTable . '"',
            $row->sql,
            1,
            $tableMatches
        );
        if ($userNullable) {
            $nullableMatches = 1;
        } else {
            $definition = preg_replace(
                '/("user_id"\s+[^,]+?)\s+NOT NULL(?=\s*,)/i',
                '$1',
                $definition,
                1,
                $nullableMatches
            );
        }
        $definition = preg_replace(
            '/(FOREIGN KEY\s*\(\s*["`]?user_id["`]?\s*\)[^,]*?\bON DELETE\s+)CASCADE\b/i',
            '$1SET NULL',
            $definition,
            1,
            $foreignKeyMatches
        );

        if ($tableMatches !== 1 || $nullableMatches !== 1 || $foreignKeyMatches !== 1) {
            throw new RuntimeException('No se pudo transformar de forma segura la clave foránea de usuario.');
        }

        $indexesAndTriggers = DB::select(
            "SELECT sql FROM sqlite_master WHERE tbl_name = ? AND type IN ('index', 'trigger') AND sql IS NOT NULL",
            [$tableName]
        );
        $columns = DB::select('PRAGMA table_info("' . $tableName . '")');
        $columnList = implode(', ', array_map(function ($column) {
            return '"' . str_replace('"', '""', $column->name) . '"';
        }, $columns));

        DB::statement($definition);
        DB::statement(
            'INSERT INTO "' . $temporaryTable . '" (' . $columnList . ') ' .
            'SELECT ' . $columnList . ' FROM "' . $tableName . '"'
        );
        DB::statement('DROP TABLE "' . $tableName . '"');
        DB::statement('ALTER TABLE "' . $temporaryTable . '" RENAME TO "' . $tableName . '"');

        $foreignKeysAfter = $this->sqliteForeignKeyDefinitions($tableName);
        $expectedForeignKeys = $foreignKeysBefore;
        foreach ($expectedForeignKeys as &$foreignKey) {
            if ($foreignKey['from'] === 'user_id') {
                $foreignKey['on_delete'] = 'SET NULL';
            }
        }
        unset($foreignKey);
        $this->sortSqliteForeignKeys($expectedForeignKeys);
        $this->sortSqliteForeignKeys($foreignKeysAfter);

        if ($foreignKeysAfter !== $expectedForeignKeys) {
            throw new RuntimeException('La reconstrucción alteró una definición de clave foránea inesperada.');
        }

        foreach ($indexesAndTriggers as $item) {
            DB::statement($item->sql);
        }
    }

    private function sqliteForeignKeyDefinitions(string $tableName): array
    {
        $quotedTable = '"' . str_replace('"', '""', $tableName) . '"';
        $foreignKeys = array_map(function ($foreignKey) {
            return [
                'seq' => (int) $foreignKey->seq,
                'table' => (string) $foreignKey->table,
                'from' => (string) $foreignKey->from,
                'to' => (string) $foreignKey->to,
                'on_update' => strtoupper((string) $foreignKey->on_update),
                'on_delete' => strtoupper((string) $foreignKey->on_delete),
                'match' => strtoupper((string) $foreignKey->match),
            ];
        }, DB::select('PRAGMA foreign_key_list(' . $quotedTable . ')'));

        $this->sortSqliteForeignKeys($foreignKeys);

        return $foreignKeys;
    }

    private function sortSqliteForeignKeys(array &$foreignKeys): void
    {
        usort($foreignKeys, function ($left, $right) {
            return strcmp(
                implode('|', array_values($left)),
                implode('|', array_values($right))
            );
        });
    }
}
