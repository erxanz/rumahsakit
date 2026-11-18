<?php

declare(strict_types=1);

namespace App\Core;

use PDO;

abstract class Model
{
    protected string $table = '';

    protected string $primaryKey = 'id';

    /** @var list<string> */
    protected array $fillable = [];

    /** @var list<string> */
    protected array $filterable = [];

    /** @var list<string> */
    protected array $searchable = [];

    /** @var list<string> */
    protected array $sortable = [];

    /** @var list<string> */
    protected array $hidden = [];

    protected function pdo(): PDO
    {
        return Database::connection();
    }

    public function table(): string
    {
        return $this->table;
    }

    public function primaryKey(): string
    {
        return $this->primaryKey;
    }

    /** @return list<string> */
    public function fillable(): array
    {
        return $this->fillable;
    }

    /** @return list<string> */
    public function filterable(): array
    {
        return $this->filterable;
    }

    /** @return list<string> */
    public function sortable(): array
    {
        return $this->sortable !== [] ? $this->sortable : $this->filterable;
    }

    /** @return list<string> */
    public function searchable(): array
    {
        return $this->searchable;
    }

    public function find(int|string $id): ?array
    {
        $stmt = $this->pdo()->prepare(
            sprintf('SELECT * FROM `%s` WHERE `%s` = :id LIMIT 1', $this->table, $this->primaryKey)
        );
        $stmt->execute(['id' => $id]);
        $row = $stmt->fetch();

        return $row === false ? null : $this->present($row);
    }

    /** @param array<string, mixed> $criteria */
    public function firstWhere(array $criteria): ?array
    {
        $rows = $this->where($criteria, 1);

        return $rows[0] ?? null;
    }

    /**
     * @param array<string, mixed> $criteria
     * @return list<array<string, mixed>>
     */
    public function where(array $criteria, ?int $limit = null): array
    {
        if ($criteria === []) {
            throw new \RuntimeException('Kriteria where tidak boleh kosong');
        }

        $clauses = [];
        $params = [];

        foreach ($criteria as $column => $value) {
            $clauses[] = "`{$column}` = :{$column}";
            $params[$column] = $value;
        }

        $sql = sprintf(
            'SELECT * FROM `%s` WHERE %s%s',
            $this->table,
            implode(' AND ', $clauses),
            $limit === null ? '' : ' LIMIT ' . (int) $limit
        );

        $stmt = $this->pdo()->prepare($sql);
        $stmt->execute($params);

        return array_map(fn (array $row): array => $this->present($row), $stmt->fetchAll());
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function paginate(
        int $page = 1,
        int $perPage = 15,
        array $filters = [],
        ?string $search = null,
        ?string $sort = null,
        string $direction = 'asc'
    ): array {
        $page = max(1, $page);
        $perPage = min(100, max(1, $perPage));

        [$where, $params] = $this->buildWhere($filters, $search);

        $countStmt = $this->pdo()->prepare(
            sprintf('SELECT COUNT(*) FROM `%s`%s', $this->table, $where)
        );
        $countStmt->execute($params);
        $total = (int) $countStmt->fetchColumn();

        $order = '';
        if ($sort !== null && in_array($sort, $this->sortable(), true)) {
            $direction = strtolower($direction) === 'desc' ? 'DESC' : 'ASC';
            $order = " ORDER BY `{$sort}` {$direction}";
        }

        $offset = ($page - 1) * $perPage;

        $stmt = $this->pdo()->prepare(sprintf(
            'SELECT * FROM `%s`%s%s LIMIT %d OFFSET %d',
            $this->table,
            $where,
            $order,
            $perPage,
            $offset
        ));
        $stmt->execute($params);

        $items = array_map(fn (array $row): array => $this->present($row), $stmt->fetchAll());

        return [
            'data' => $items,
            'meta' => [
                'page' => $page,
                'per_page' => $perPage,
                'total' => $total,
                'last_page' => max(1, (int) ceil($total / $perPage)),
            ],
        ];
    }

    /**
     * @param array<string, mixed> $data
     * @return int|string primary key hasil insert
     */
    public function insert(array $data): int|string
    {
        $columns = array_intersect_key($data, array_flip($this->fillable));

        if ($columns === []) {
            throw new \RuntimeException('Tidak ada kolom fillable untuk insert ke ' . $this->table);
        }

        $names = implode(', ', array_map(static fn (string $c): string => "`{$c}`", array_keys($columns)));
        $placeholders = implode(', ', array_map(static fn (string $c): string => ":{$c}", array_keys($columns)));

        $stmt = $this->pdo()->prepare(
            sprintf('INSERT INTO `%s` (%s) VALUES (%s)', $this->table, $names, $placeholders)
        );
        $stmt->execute($columns);

        if (array_key_exists($this->primaryKey, $columns)) {
            $key = $columns[$this->primaryKey];

            return is_numeric($key) ? (int) $key : (string) $key;
        }

        $lastId = $this->pdo()->lastInsertId();

        return is_numeric($lastId) ? (int) $lastId : $lastId;
    }

    /** @param array<string, mixed> $data */
    public function updateById(int|string $id, array $data): bool
    {
        $columns = array_intersect_key($data, array_flip($this->fillable));
        unset($columns[$this->primaryKey]);

        if ($columns === []) {
            return $this->find($id) !== null;
        }

        $sets = implode(', ', array_map(
            static fn (string $c): string => "`{$c}` = :{$c}",
            array_keys($columns)
        ));

        $stmt = $this->pdo()->prepare(
            sprintf('UPDATE `%s` SET %s WHERE `%s` = :__pk', $this->table, $sets, $this->primaryKey)
        );

        $columns['__pk'] = $id;
        $stmt->execute($columns);

        return $stmt->rowCount() > 0 || $this->find($id) !== null;
    }

    public function deleteById(int|string $id): bool
    {
        $stmt = $this->pdo()->prepare(
            sprintf('DELETE FROM `%s` WHERE `%s` = :id', $this->table, $this->primaryKey)
        );
        $stmt->execute(['id' => $id]);

        return $stmt->rowCount() > 0;
    }

    /** @return array{0:string,1:array<string,mixed>} */
    private function buildWhere(array $filters, ?string $search): array
    {
        $clauses = [];
        $params = [];

        foreach ($filters as $column => $value) {
            if (!in_array($column, $this->filterable(), true)) {
                continue;
            }

            if ($value === null || $value === '') {
                continue;
            }

            if (is_array($value)) {
                if ($value === []) {
                    $clauses[] = '1 = 0';
                    continue;
                }

                $placeholders = [];
                foreach ($value as $index => $item) {
                    $key = "{$column}_{$index}";
                    $placeholders[] = ":{$key}";
                    $params[$key] = $item;
                }

                $clauses[] = "`{$column}` IN (" . implode(', ', $placeholders) . ')';
            } elseif (is_string($value) && str_starts_with($value, '!')) {
                $clauses[] = "`{$column}` <> :{$column}";
                $params[$column] = substr($value, 1);
            } elseif (is_string($value) && str_contains($value, ':')) {
                [$op, $val] = explode(':', $value, 2);
                $allowed = ['gt', 'gte', 'lt', 'lte', 'like'];

                if (in_array($op, $allowed, true)) {
                    $sqlOp = match ($op) {
                        'gt' => '>',
                        'gte' => '>=',
                        'lt' => '<',
                        'lte' => '<=',
                        'like' => 'LIKE',
                    };

                    $clauses[] = "`{$column}` {$sqlOp} :{$column}";
                    $params[$column] = $op === 'like' ? '%' . $val . '%' : $val;
                }
            } else {
                $clauses[] = "`{$column}` = :{$column}";
                $params[$column] = $value;
            }
        }

        if ($search !== null && $search !== '' && $this->searchable() !== []) {
            $likes = [];
            foreach ($this->searchable() as $index => $column) {
                $likes[] = "`{$column}` LIKE :search_{$index}";
                $params["search_{$index}"] = '%' . $search . '%';
            }

            $clauses[] = '(' . implode(' OR ', $likes) . ')';
        }

        $where = $clauses === [] ? '' : ' WHERE ' . implode(' AND ', $clauses);

        return [$where, $params];
    }

    /** @param array<string, mixed> $row */
    private function present(array $row): array
    {
        foreach ($this->hidden as $column) {
            unset($row[$column]);
        }

        return $row;
    }
}
