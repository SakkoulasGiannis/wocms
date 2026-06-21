<?php

namespace Weborange\VisualBuilder\Contracts;

/**
 * The host app implements this to decide WHERE the builder output is saved and
 * WHICH existing sections can be loaded back. The package itself stores nothing.
 */
interface BuilderPersistence
{
    /**
     * Targets the builder can save into (e.g. pages).
     *
     * @return array<int, array{id:int|string, label:string, mode?:string, meta?:array}>
     */
    public function targets(): array;

    /**
     * Existing editable sections for a target, with their stored HTML so the
     * builder can load one back for editing.
     *
     * @return array<int, array{id:int|string, name:string, html:string, is_loop?:bool, source?:?string}>
     */
    public function sections(int|string $targetId): array;

    /**
     * The current content of a target, as HTML, to seed the builder with for
     * editing (migrating an existing page into the builder). Return null/'' to
     * start blank.
     */
    public function seedFor(int|string $targetId): ?string;

    /**
     * Persist the builder output.
     *
     * @param  array{
     *     target_id:int|string,
     *     section_id?:int|string|null,
     *     html:string,
     *     name?:string,
     *     convert?:bool,
     *     loop?:array{source:string, columns:int, limit:int, order_by:string, order_dir:string, heading:string}|null
     * }  $payload
     * @return array{success:bool, message:string, section_id?:int|string, url?:?string, edit_url?:?string, needs_convert?:bool}
     */
    public function save(array $payload): array;
}
