<?php

declare(strict_types=1);

namespace App\Core;

use PDOException;

abstract class CrudController extends Controller
{
    /** @var class-string<Model> */
    protected string $modelClass = '';

    /** @var array<string, string|list<string>> */
    protected array $storeRules = [];

    /** @var array<string, string|list<string>> */
    protected array $updateRules = [];

    protected string $resourceName = 'data';

    protected function model(): Model
    {
        if ($this->modelClass === '' || !is_subclass_of($this->modelClass, Model::class)) {
            throw new \RuntimeException(static::class . ' belum mendefinisikan modelClass yang valid');
        }

        return new $this->modelClass();
    }

    public function index(Request $request): Response
    {
        $model = $this->model();
        $query = $request->query();

        $filters = array_merge(
            $this->baseFilters($request),
            $this->extractFilters($query, $model)
        );

        $result = $model->paginate(
            page: (int) ($query['page'] ?? 1),
            perPage: (int) ($query['per_page'] ?? 15),
            filters: $filters,
            search: isset($query['q']) ? trim((string) $query['q']) : null,
            sort: isset($query['sort']) ? (string) $query['sort'] : null,
            direction: (string) ($query['order'] ?? 'asc')
        );

        return Response::ok($result);
    }

    public function show(Request $request): Response
    {
        $model = $this->model();
        $row = $model->find($request->intParam('id'));

        if ($row === null) {
            throw HttpException::notFound(ucfirst($this->resourceName) . ' tidak ditemukan');
        }

        $this->authorizeRow($request, $row);

        return Response::ok($row);
    }

    public function store(Request $request): Response
    {
        $model = $this->model();
        $data = $this->beforeValidate($request, $request->json());

        $this->validate($data, $this->storeRules);
        $payload = $this->filterFillable($model, $data);
        $payload = $this->beforeStore($request, $payload);

        try {
            $id = $model->insert($payload);
        } catch (PDOException $e) {
            throw $this->mapDatabaseException($e);
        }

        $this->afterStore($request, $id, $payload);
        $this->audit(
            $request,
            'Menambahkan ' . $this->resourceName,
            $model->table(),
            $id,
            $payload
        );

        return Response::created($model->find($id), ucfirst($this->resourceName) . ' berhasil ditambahkan');
    }

    public function update(Request $request): Response
    {
        $model = $this->model();
        $id = $request->intParam('id');
        $row = $model->find($id);

        if ($row === null) {
            throw HttpException::notFound(ucfirst($this->resourceName) . ' tidak ditemukan');
        }

        $this->authorizeRow($request, $row);

        $data = $request->json();
        $rules = $this->updateRules !== [] ? $this->updateRules : $this->storeRules;

        $this->validate($data, $rules, [
            'table' => $model->table(),
            'pk' => $model->primaryKey(),
            'value' => $row[$model->primaryKey()],
        ]);

        $payload = $this->filterFillable($model, $data);
        $payload = $this->beforeUpdate($request, $row, $payload);

        if ($payload === []) {
            return Response::ok($row, 'Tidak ada perubahan yang diterapkan');
        }

        try {
            $model->updateById($id, $payload);
        } catch (PDOException $e) {
            throw $this->mapDatabaseException($e);
        }

        $this->afterUpdate($request, $id, $payload);
        $this->audit(
            $request,
            'Mengubah ' . $this->resourceName,
            $model->table(),
            $id,
            $payload
        );

        return Response::ok($model->find($id), ucfirst($this->resourceName) . ' berhasil diperbarui');
    }

    public function destroy(Request $request): Response
    {
        $model = $this->model();
        $id = $request->intParam('id');
        $row = $model->find($id);

        if ($row === null) {
            throw HttpException::notFound(ucfirst($this->resourceName) . ' tidak ditemukan');
        }

        $this->authorizeRow($request, $row);
        $this->beforeDestroy($request, $row);

        try {
            $model->deleteById($id);
        } catch (PDOException $e) {
            throw $this->mapDatabaseException($e);
        }

        $this->audit($request, 'Menghapus ' . $this->resourceName, $model->table(), $id);

        return Response::ok([], ucfirst($this->resourceName) . ' berhasil dihapus');
    }

    /**
     * Filter wajib yang selalu diterapkan (misal: scoping data pasien).
     *
     * @return array<string, mixed>
     */
    protected function baseFilters(Request $request): array
    {
        return [];
    }

    protected function authorizeRow(Request $request, array $row): void
    {
    }

    /**
     * Transformasi data sebelum validasi (misal: inject nilai dari konteks user).
     *
     * @param array<string, mixed> $data
     * @return array<string, mixed>
     */
    protected function beforeValidate(Request $request, array $data): array
    {
        return $data;
    }

    /** @param array<string, mixed> $payload */
    protected function beforeStore(Request $request, array $payload): array
    {
        return $payload;
    }

    /** @param array<string, mixed> $payload */
    protected function beforeUpdate(Request $request, array $row, array $payload): array
    {
        return $payload;
    }

    protected function afterStore(Request $request, int|string $id, array $payload): void
    {
    }

    protected function afterUpdate(Request $request, int|string $id, array $payload): void
    {
    }

    protected function beforeDestroy(Request $request, array $row): void
    {
    }

    /**
     * @param array<string, mixed> $query
     * @return array<string, mixed>
     */
    private function extractFilters(array $query, Model $model): array
    {
        $filters = [];

        foreach ($model->filterable() as $column) {
            if (array_key_exists($column, $query)) {
                $filters[$column] = $query[$column];
            }
        }

        return $filters;
    }

    /** @param array<string, mixed> $data @return array<string, mixed> */
    private function filterFillable(Model $model, array $data): array
    {
        return array_intersect_key($data, array_flip($model->fillable()));
    }

    private function mapDatabaseException(PDOException $e): HttpException
    {
        $sqlState = $e->errorInfo[0] ?? null;

        if ($sqlState === '23000') {
            $message = $e->errorInfo[2] ?? '';

            if (str_contains($message, 'Duplicate entry')) {
                return HttpException::conflict('Data sudah ada (duplikat)');
            }

            if (str_contains($message, 'foreign key constraint')) {
                return HttpException::conflict(
                    'Data tidak dapat dihapus karena masih digunakan oleh data lain'
                );
            }

            return HttpException::conflict('Pelanggaran integritas data');
        }

        if ($sqlState === '23001') {
            return HttpException::conflict('Data tidak dapat dihapus karena masih digunakan');
        }

        return HttpException::badRequest('Terjadi kesalahan pada database');
    }
}
