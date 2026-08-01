<?php

declare(strict_types=1);

namespace CraftCms\Cms\Cp\FormDefinitions\Elements;

class KeyedTableInput extends InputElement
{
    /**
     * @var list<array{
     *     key: string,
     *     label: string,
     *     placeholder?: string,
     *     code?: bool,
     * }>
     */
    private array $columns = [];

    /**
     * @var list<array{
     *     key: string,
     *     label: string,
     * }>
     */
    private array $rows = [];

    public static function type(): string
    {
        return 'craft:keyed-table-input';
    }

    /**
     * @param  list<array{
     *     key: string,
     *     label: string,
     *     placeholder?: string,
     *     code?: bool,
     * }>  $columns
     */
    public function columns(array $columns): static
    {
        $this->columns = $columns;

        return $this;
    }

    /**
     * @param  list<array{
     *     key: string,
     *     label: string,
     * }>  $rows
     */
    public function rows(array $rows): static
    {
        $this->rows = $rows;

        return $this;
    }

    #[\Override]
    protected function props(): array
    {
        return [
            'columns' => $this->columns,
            'rows' => $this->rows,
        ];
    }
}
