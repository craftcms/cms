<?php

declare(strict_types=1);

namespace Workbench\App\Http\Controllers;

use CraftCms\Cms\Cp\Components\Button;
use CraftCms\Cms\Cp\Components\ButtonGroup;
use CraftCms\Cms\Form\Controls\Choice;
use CraftCms\Cms\Form\Controls\Number;
use CraftCms\Cms\Form\Controls\Text;
use CraftCms\Cms\Form\Form;
use CraftCms\Cms\Form\FormContext;
use CraftCms\Cms\Form\FormHtmlRenderer;
use CraftCms\Cms\Form\FormResolver;
use CraftCms\Cms\Form\Nodes\Field;
use CraftCms\Cms\Form\Nodes\Table;
use CraftCms\Cms\Http\RespondsWithFlash;
use CraftCms\Cms\Http\Responses\CpScreenResponse;
use CraftCms\Cms\Support\Html;
use CraftCms\Cms\Support\Str;
use CraftCms\Cms\Support\Url;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Exercises the Table node against a session-backed list of fake products.
 */
class TableNodeController
{
    use RespondsWithFlash;

    public const array STORIES = [
        'local' => 'Upfront rows',
        'endpoint' => 'Data endpoint',
        'bare' => 'Bare table',
        'empty' => 'Empty',
    ];

    private const string SessionKey = 'workbench.tableNode.products';

    private const array Categories = ['Apparel', 'Books', 'Garden', 'Kitchen', 'Toys'];

    public function __construct(
        private readonly FormResolver $resolver,
        private readonly FormHtmlRenderer $htmlRenderer,
    ) {}

    public function index(): RedirectResponse
    {
        return redirect(Url::cpUrl('workbench/table/local/vue'));
    }

    public function show(string $story, string $renderer = 'vue'): CpScreenResponse
    {
        $payload = $this->resolver->resolve(Form::make([$this->table($story)]), new FormContext);
        $url = "workbench/table/{$story}";

        $response = new CpScreenResponse()
            ->title('Table node: '.self::STORIES[$story])
            ->addCrumb('Table node', 'workbench/table')
            ->additionalButtonsHtml(
                Button::make()->label('Reset data')->href(Url::cpUrl('workbench/table/reset'))->toHtml().
                ButtonGroup::make()->buttons([
                    Button::make()->label('Vue')->href(Url::cpUrl("{$url}/vue"))->active($renderer === 'vue'),
                    Button::make()->label('HTML')->href(Url::cpUrl("{$url}/html"))->active($renderer === 'html'),
                ])->toHtml(),
            );

        if ($renderer === 'html') {
            return $response->contentHtml($this->htmlRenderer->render($payload));
        }

        return $response->inertiaPage('Form', ['form' => $payload]);
    }

    public function reset(Request $request): RedirectResponse
    {
        $request->session()->forget(self::SessionKey);

        return back(fallback: Url::cpUrl('workbench/table'));
    }

    public function edit(string $id): CpScreenResponse
    {
        $product = $id === 'new' ? null : $this->products()->firstWhere('id', (int) $id);

        abort_if($id !== 'new' && $product === null, 404);

        $form = Form::make([
            Field::make('Name', Text::make('name')->value($product['name'] ?? ''))->required(),
            Field::make('SKU', Text::make('sku')->value($product['sku'] ?? '')),
            Field::make('Price', Number::make('price')->value($product['price'] ?? 0)),
            Field::make('Category', Choice::make('category')
                ->options($this->categoryOptions())
                ->value($product['category'] ?? self::Categories[0])),
        ]);

        return new CpScreenResponse()
            ->title($product['name'] ?? 'New product')
            ->addCrumb('Table node', 'workbench/table')
            ->redirectUrl('workbench/table')
            ->inertiaPage('Form', [
                'form' => $this->resolver->resolve($form, new FormContext),
                'submit' => ['method' => 'post', 'url' => Url::actionUrl("workbench/table/products/{$id}")],
            ]);
    }

    public function save(Request $request, string $id): Response
    {
        $attributes = $request->validate([
            'name' => ['required', 'string'],
            'sku' => ['nullable', 'string'],
            'price' => ['nullable', 'numeric'],
            'category' => ['required', 'string'],
        ]);

        $products = $this->products();

        if ($id === 'new') {
            $products->push([
                ...$this->fakeProduct((int) $products->max('id') + 1),
                ...$attributes,
            ]);
        } else {
            $products = $products->map(fn (array $product) => $product['id'] === (int) $id
                ? [...$product, ...$attributes]
                : $product);
        }

        $this->store($request, $products);

        return $this->asSuccess('Product saved.');
    }

    public function data(Request $request): JsonResponse
    {
        $perPage = max(1, $request->integer('per_page', 25));
        $products = $this->products();

        if ($search = Str::lower($request->string('search')->toString())) {
            $products = $products->filter(fn (array $product) => Str::contains(
                Str::lower("{$product['name']} {$product['sku']} {$product['category']}"),
                $search,
            ));
        }

        if ($status = $request->string('status')->toString()) {
            $products = $products->where('status', $status);
        }

        foreach (array_reverse($request->array('sort')) as $sort) {
            $products = $products->sortBy($sort['field'], descending: ($sort['direction'] ?? 'asc') === 'desc');
        }

        $products = $products->values();
        $total = $products->count();
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = min(max(1, $request->integer('page', 1)), $lastPage);
        $rows = $products->forPage($page, $perPage)->values();

        return new JsonResponse([
            'data' => Table::prepareRows($rows->map($this->row(...))->all()),
            'pagination' => [
                'total' => $total,
                'per_page' => $perPage,
                'current_page' => $page,
                'last_page' => $lastPage,
                'next_page_url' => $page < $lastPage ? (string) ($page + 1) : null,
                'prev_page_url' => $page > 1 ? (string) ($page - 1) : null,
                'from' => $total ? ($page - 1) * $perPage + 1 : 0,
                'to' => min($total, $page * $perPage),
            ],
        ]);
    }

    /**
     * Upfront tables post the full new order as JSON-encoded `ids`; endpoint tables post
     * one `id` and its new absolute `toPosition`.
     */
    public function reorder(Request $request): JsonResponse
    {
        $products = $this->products()->keyBy('id');

        if ($request->has('ids')) {
            $ids = array_map(intval(...), json_decode($request->string('ids')->toString(), true) ?: []);
            $reordered = collect($ids)->map(fn (int $id) => $products->get($id))->filter();
            $products = $reordered->concat($products->except($ids))->values();
        } else {
            $products = $this->moveTo($products->values(), $request->integer('id'), $request->integer('toPosition'));
        }

        $this->store($request, $products);

        return new JsonResponse(['success' => true]);
    }

    public function moveToPage(Request $request): JsonResponse
    {
        $position = ($request->integer('page') - 1) * $request->integer('per_page');
        $this->store($request, $this->moveTo($this->products(), $request->integer('id'), $position));

        return new JsonResponse(['success' => true]);
    }

    public function delete(Request $request): JsonResponse
    {
        $ids = $request->has('ids')
            ? array_map(intval(...), $request->array('ids'))
            : [$request->integer('id')];

        $this->store($request, $this->products()->reject(fn (array $product) => in_array($product['id'], $ids, true)));

        $message = $request->filled('reason')
            ? "Deleted ({$request->string('reason')})."
            : (count($ids) === 1 ? 'Product deleted.' : count($ids).' products deleted.');

        return new JsonResponse(['success' => true, 'message' => $message]);
    }

    public function deleteModal(Request $request): JsonResponse
    {
        $product = $this->products()->firstWhere('id', $request->integer('id'));

        abort_if($product === null, 404);

        return $this->modal(Form::make([
            Field::make('Reason', Choice::make('reason')->options([
                ['label' => 'Discontinued', 'value' => 'discontinued'],
                ['label' => 'Duplicate', 'value' => 'duplicate'],
                ['label' => 'Other', 'value' => 'other'],
            ])->value('discontinued'))->instructions("Why is “{$product['name']}” being deleted?"),
        ]), "Delete {$product['name']}", 'Delete');
    }

    public function setStatus(Request $request): JsonResponse
    {
        return $this->updateSelected($request, ['status' => $request->string('status')->toString()]);
    }

    public function setCategory(Request $request): JsonResponse
    {
        return $this->updateSelected($request, ['category' => $request->string('category')->toString()]);
    }

    /** @param  array<string, mixed>  $attributes */
    private function updateSelected(Request $request, array $attributes): JsonResponse
    {
        $ids = array_map(intval(...), $request->array('ids'));

        $this->store($request, $this->products()->map(fn (array $product) => in_array($product['id'], $ids, true)
            ? [...$product, ...$attributes]
            : $product));

        return new JsonResponse(['success' => true]);
    }

    public function duplicate(Request $request): JsonResponse
    {
        $products = $this->products();
        $nextId = (int) $products->max('id');

        $copies = $products
            ->whereIn('id', array_map(intval(...), $request->array('ids')))
            ->map(fn (array $product) => [
                ...$product,
                'id' => ++$nextId,
                'name' => "{$product['name']} (copy)",
                'sku' => "{$product['sku']}-COPY",
            ]);

        $this->store($request, $products->concat($copies));

        return new JsonResponse(['success' => true]);
    }

    public function adjustStockModal(Request $request): JsonResponse
    {
        $product = $this->products()->firstWhere('id', $request->integer('id'));

        abort_if($product === null, 404);

        return $this->modal(Form::make([
            Field::make('Stock', Number::make('stock')->value($product['stock']))->required(),
        ]), "Adjust stock for {$product['name']}", 'Save');
    }

    public function adjustStock(Request $request): JsonResponse
    {
        $request->validate(['stock' => ['required', 'integer', 'min:0']]);

        $id = $request->integer('id');
        $this->store($request, $this->products()->map(fn (array $product) => $product['id'] === $id
            ? [...$product, 'stock' => $request->integer('stock')]
            : $product));

        return new JsonResponse(['success' => true, 'message' => 'Stock updated.']);
    }

    private function table(string $story): Table
    {
        $columns = [
            ['key' => 'name', 'label' => 'Name', 'sortable' => true],
            ['key' => 'sku', 'label' => 'SKU'],
            ['key' => 'category', 'label' => 'Category', 'sortable' => true],
            ['key' => 'price', 'label' => 'Price', 'sortable' => true],
            ['key' => 'stock', 'label' => 'Stock', 'sortable' => true],
            ['key' => 'links', 'label' => 'Links'],
            ['key' => 'flags', 'label' => 'Flags'],
            ['key' => 'notes', 'label' => 'Notes'],
        ];

        $statusActions = [
            ['label' => 'Enabled', 'url' => 'workbench/table/set-status', 'params' => ['status' => 'enabled'], 'fill' => 'teal'],
            ['label' => 'Pending', 'url' => 'workbench/table/set-status', 'params' => ['status' => 'pending'], 'fill' => 'orange'],
            ['label' => 'Disabled', 'url' => 'workbench/table/set-status', 'params' => ['status' => 'disabled']],
        ];

        $bulkActions = [
            ['label' => 'Duplicate', 'url' => 'workbench/table/duplicate'],
            ['label' => 'Categorize', 'items' => array_map(fn (string $category) => [
                'label' => $category,
                'url' => 'workbench/table/set-category',
                'params' => ['category' => $category],
            ], self::Categories)],
        ];

        $statusFilter = [
            ['value' => 'enabled', 'label' => 'Enabled'],
            ['value' => 'pending', 'label' => 'Pending'],
            ['value' => 'disabled', 'label' => 'Disabled'],
        ];

        return match ($story) {
            'local' => Table::make('products-local')
                ->columns($columns)
                ->rows($this->products()->take(20)->map(function (array $product) {
                    $row = $this->row($product);
                    $row['_deletable'] = $product['id'] !== 1;

                    return $row;
                })->values()->all())
                ->createActionMenu('New product', [
                    ['label' => 'Blank product', 'url' => Url::cpUrl('workbench/table/products/new')],
                    ['label' => 'From template', 'url' => Url::cpUrl('workbench/table/products/new')],
                ])
                ->searchable('Search products')
                ->statusFilter($statusFilter)
                ->toggleableColumns(['sku', 'notes'])
                ->reorderable('workbench/table/reorder', 'Products reordered.', 'Couldn’t reorder products.')
                ->deletable('workbench/table/delete', 'Delete this product?', bulk: true)
                ->bulkActions($bulkActions)
                ->statusActions($statusActions)
                ->emptyMessage('No products match.'),
            'endpoint' => Table::make('products-endpoint')
                ->columns($columns)
                ->dataUrl('workbench/table/data', 25, [10, 25, 50])
                ->moveToPageUrl('workbench/table/move-to-page')
                ->createAction('New product', Url::cpUrl('workbench/table/products/new'))
                ->createActionInPageHeader()
                ->searchable()
                ->statusFilter($statusFilter)
                ->toggleableColumns(['links', 'flags', 'notes'])
                ->reorderable('workbench/table/reorder')
                ->deletable('workbench/table/delete', bulk: true, modalUrl: 'workbench/table/delete-modal')
                ->bulkActions($bulkActions)
                ->statusActions($statusActions)
                ->bordered(),
            'bare' => Table::make('products-bare')
                ->columns([
                    ['key' => 'name', 'label' => 'Name'],
                    ['key' => 'category', 'label' => 'Category'],
                    ['key' => 'price', 'label' => 'Price'],
                ])
                ->rows($this->products()->take(5)->map($this->row(...))->values()->all())
                ->bordered(false)
                ->showFooter(false),
            'empty' => Table::make('products-empty')
                ->columns($columns)
                ->rows([])
                ->createAction('New product', Url::cpUrl('workbench/table/products/new'))
                ->emptyMessage('No products exist yet.'),
            default => abort(404),
        };
    }

    /**
     * Covers each cell shape the Table node accepts.
     *
     * @param  array<string, mixed>  $product
     * @return array<string, mixed>
     */
    private function row(array $product): array
    {
        return [
            'id' => $product['id'],
            '_status' => $product['status'],
            'name' => [
                'label' => $product['name'],
                'url' => Url::cpUrl("workbench/table/products/{$product['id']}"),
                'slideout' => true,
            ],
            'sku' => $product['sku'],
            'category' => $product['category'],
            'price' => '$'.number_format($product['price'], 2),
            'stock' => [
                'label' => (string) $product['stock'],
                'items' => [
                    ['label' => 'Edit product', 'url' => Url::cpUrl("workbench/table/products/{$product['id']}")],
                    [
                        'label' => 'Adjust stock…',
                        'modalUrl' => 'workbench/table/adjust-stock-modal',
                        'actionUrl' => 'workbench/table/adjust-stock',
                        'params' => ['id' => $product['id']],
                    ],
                ],
            ],
            'links' => [
                ['label' => 'Docs', 'url' => 'https://craftcms.com/docs'],
                ['label' => 'No URL'],
            ],
            'flags' => $product['stock'] < 10
                ? ['icon' => 'triangle-exclamation', 'label' => 'Low stock']
                : ['icon' => 'check', 'label' => null],
            'notes' => ['html' => Html::tag('em', Html::encode("Added #{$product['id']}"))],
            '_search' => "{$product['name']} {$product['sku']} {$product['category']}",
            '_sort' => ['price' => $product['price'], 'stock' => $product['stock']],
        ];
    }

    private function modal(Form $form, string $title, string $submitLabel): JsonResponse
    {
        return new JsonResponse([
            'form' => $this->resolver->resolve($form, new FormContext),
            'title' => $title,
            'submitLabel' => $submitLabel,
        ]);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $products
     * @return Collection<int, array<string, mixed>>
     */
    private function moveTo(Collection $products, int $id, int $position): Collection
    {
        $moved = $products->firstWhere('id', $id);

        if ($moved === null) {
            return $products;
        }

        $products = $products->reject(fn (array $product) => $product['id'] === $id)->values();
        $products->splice(max(0, min($position, $products->count())), 0, [$moved]);

        return $products;
    }

    /** @return Collection<int, array<string, mixed>> */
    private function products(): Collection
    {
        $stored = session()->get(self::SessionKey);

        return collect(is_array($stored) ? $stored : array_map($this->fakeProduct(...), range(1, 120)));
    }

    /** @param  Collection<int, array<string, mixed>>  $products */
    private function store(Request $request, Collection $products): void
    {
        $request->session()->put(self::SessionKey, $products->values()->all());
    }

    /** @return array<string, mixed> */
    private function fakeProduct(int $id): array
    {
        $adjectives = ['Classic', 'Deluxe', 'Mini', 'Organic', 'Rustic', 'Smart', 'Vintage', 'Wireless'];
        $nouns = ['Apron', 'Atlas', 'Blender', 'Bucket', 'Hoodie', 'Kettle', 'Kite', 'Novel', 'Planter', 'Puzzle', 'Shears', 'Socks'];

        return [
            'id' => $id,
            'name' => $adjectives[$id % count($adjectives)].' '.$nouns[($id * 7) % count($nouns)].' '.$id,
            'sku' => sprintf('SKU-%04d', $id),
            'category' => self::Categories[($id * 3) % count(self::Categories)],
            'price' => round(5 + (($id * 37) % 200) + ($id % 100) / 100, 2),
            'stock' => ($id * 13) % 60,
            'status' => ['enabled', 'enabled', 'pending', 'disabled'][$id % 4],
        ];
    }

    /** @return list<array{label: string, value: string}> */
    private function categoryOptions(): array
    {
        return array_map(fn (string $category) => ['label' => $category, 'value' => $category], self::Categories);
    }
}
