<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Turns the public content columns into {"en": ..., "am": ...} JSON for
 * spatie/laravel-translatable. Existing values become English.
 *
 * Works on SQLite and MySQL: each column goes string/text → TEXT, the values are
 * rewritten, then TEXT → JSON (MySQL refuses to convert non-JSON strings to JSON
 * directly). down() reverses it and keeps the English value.
 */
return new class extends Migration
{
    /**
     * Plain columns: [type, length, nullable] as they were before this migration.
     *
     * @var array<string, array<string, array{0: 'string'|'text', 1: int|null, 2: bool}>>
     */
    private const COLUMNS = [
        'profiles' => [
            'headline' => ['string', 160, false],
            'lede' => ['string', 400, false],
            'about_statement' => ['string', 240, true],
            'location' => ['string', 120, true],
            'meta_description' => ['string', 300, true],
        ],
        'projects' => [
            'title' => ['string', 120, false],
            'subtitle' => ['string', 200, true],
            'summary' => ['string', 300, false],
            'problem' => ['text', null, false],
            'role' => ['text', null, false],
            'solution' => ['text', null, false],
            'outcome' => ['text', null, false],
        ],
        'skill_groups' => [
            'label' => ['string', 60, false],
        ],
        'experiences' => [
            'title' => ['string', 140, false],
            'organization' => ['string', 160, false],
            'location' => ['string', 120, true],
            'description' => ['text', null, true],
        ],
        'process_steps' => [
            'title' => ['string', 80, false],
            'description' => ['string', 300, false],
        ],
    ];

    /** JSON columns that are already JSON: the whole value is wrapped (bio paragraphs). */
    private const WRAPPED_JSON = ['profiles' => ['bio']];

    /** JSON lists of {value, label} where only the label becomes translatable. */
    private const LABELLED_LISTS = ['profiles' => ['facts'], 'projects' => ['metrics']];

    public function up(): void
    {
        foreach (self::COLUMNS as $table => $columns) {
            $this->changeColumns($table, $columns, static fn (Blueprint $t, string $column, bool $nullable) => $t->text($column)->nullable($nullable));
            $this->rewrite($table, array_keys($columns), static fn (mixed $value): ?string => $value === null ? null : self::json(['en' => (string) $value]));
            $this->changeColumns($table, $columns, static fn (Blueprint $t, string $column, bool $nullable) => $t->json($column)->nullable($nullable));
        }

        foreach (self::WRAPPED_JSON as $table => $columns) {
            $this->rewrite($table, $columns, static fn (mixed $value): ?string => $value === null ? null : self::json(['en' => json_decode((string) $value, true) ?? []]));
        }

        foreach (self::LABELLED_LISTS as $table => $columns) {
            $this->rewrite($table, $columns, static fn (mixed $value): ?string => $value === null ? null : self::json(array_map(
                static fn (array $item): array => [...$item, 'label' => is_array($item['label'] ?? null) ? $item['label'] : ['en' => (string) ($item['label'] ?? '')]],
                json_decode((string) $value, true) ?? [],
            )));
        }
    }

    public function down(): void
    {
        foreach (self::LABELLED_LISTS as $table => $columns) {
            $this->rewrite($table, $columns, static fn (mixed $value): ?string => $value === null ? null : self::json(array_map(
                static fn (array $item): array => [...$item, 'label' => is_array($item['label'] ?? null) ? self::english($item['label']) : $item['label']],
                json_decode((string) $value, true) ?? [],
            )));
        }

        foreach (self::WRAPPED_JSON as $table => $columns) {
            $this->rewrite($table, $columns, static fn (mixed $value): ?string => $value === null ? null : self::json(self::english(json_decode((string) $value, true)) ?? []));
        }

        foreach (self::COLUMNS as $table => $columns) {
            $this->changeColumns($table, $columns, static fn (Blueprint $t, string $column, bool $nullable) => $t->text($column)->nullable($nullable));
            $this->rewrite($table, array_keys($columns), static function (mixed $value): ?string {
                $english = $value === null ? null : self::english(json_decode((string) $value, true));

                return is_string($english) ? $english : null;
            });
            $this->changeColumns($table, $columns, static function (Blueprint $t, string $column, bool $nullable, array $definition) {
                [$type, $length] = $definition;

                return ($type === 'string' ? $t->string($column, (int) $length) : $t->text($column))->nullable($nullable);
            });
        }
    }

    /**
     * @param  array<string, array{0: string, 1: int|null, 2: bool}>  $columns
     * @param  Closure(Blueprint, string, bool, array{0: string, 1: int|null, 2: bool}): mixed  $define
     */
    private function changeColumns(string $table, array $columns, Closure $define): void
    {
        Schema::table($table, static function (Blueprint $t) use ($columns, $define): void {
            foreach ($columns as $column => $definition) {
                $define($t, $column, $definition[2], $definition)->change();
            }
        });
    }

    /**
     * @param  list<string>  $columns
     * @param  Closure(mixed): ?string  $transform
     */
    private function rewrite(string $table, array $columns, Closure $transform): void
    {
        DB::table($table)->select(['id', ...$columns])->orderBy('id')->each(static function (object $row) use ($table, $columns, $transform): void {
            $values = [];
            foreach ($columns as $column) {
                $values[$column] = $transform($row->{$column});
            }
            DB::table($table)->where('id', $row->id)->update($values);
        });
    }

    /** The English value of a {locale: value} map, else the first non-empty one. */
    private static function english(mixed $translations): mixed
    {
        if (! is_array($translations) || array_is_list($translations)) {
            return $translations;
        }

        return $translations['en'] ?? collect($translations)->first(static fn (mixed $value): bool => filled($value));
    }

    private static function json(mixed $value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);
    }
};
