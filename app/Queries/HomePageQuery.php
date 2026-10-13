<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\Discipline;
use App\Enums\InquiryType;
use App\Http\Resources\ExperienceResource;
use App\Http\Resources\NoteSummaryResource;
use App\Http\Resources\ProcessStepResource;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\ProjectSummaryResource;
use App\Models\Experience;
use App\Models\Note;
use App\Models\ProcessStep;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SkillGroup;
use App\Support\Images\ResponsiveImages;
use App\Support\PortfolioCache;
use Illuminate\Http\Request;

/**
 * Assembles every prop the home page needs in a fixed number of queries,
 * resolved to plain arrays so the result is cacheable.
 */
final readonly class HomePageQuery
{
    public function __construct(private Request $request) {}

    /**
     * Anchors of the home sections that render, in page order, for the navigation:
     * sections without content are left out instead of showing empty, so their links go too.
     *
     * @return list<string>
     */
    public static function sections(): array
    {
        return array_keys(array_filter([
            'work' => true, // shows an "in preparation" state until the first case study is published
            'capabilities' => SkillGroup::query()->exists(),
            'experience' => Experience::query()->exists(),
            'about' => true,
            'contact' => true,
        ]));
    }

    /** @return array<string, mixed>|null null when no profile has been configured */
    public function get(): ?array
    {
        return PortfolioCache::remember('home', fn (): ?array => $this->build());
    }

    /** @return array<string, mixed>|null */
    private function build(): ?array
    {
        $profile = Profile::current();

        if ($profile === null) {
            return null;
        }

        $projects = Project::query()->published()->ordered()->get();
        $notes = Note::query()->published()->latestFirst()->limit(3)->get();
        app(ResponsiveImages::class)->preload([$profile->avatar_path, ...$projects->pluck('cover_path'), ...$notes->pluck('cover_path')]);

        return [
            'profile' => (new ProfileResource($profile))->resolve($this->request),
            'projects' => ProjectSummaryResource::collection($projects)->resolve($this->request),
            'capabilities' => $this->capabilities(),
            'process' => ProcessStepResource::collection(ProcessStep::query()->ordered()->get())->resolve($this->request),
            'experience' => ExperienceResource::collection(Experience::query()->ordered()->get())->resolve($this->request),
            'latestNotes' => NoteSummaryResource::collection($notes)->resolve($this->request),
            'inquiryTypes' => InquiryType::options(),
        ];
    }

    /** @return list<array{discipline: string, title: string, tagline: string, groups: list<array{label: string, items: list<string>}>}> */
    private function capabilities(): array
    {
        $groups = SkillGroup::query()->ordered()->get()->groupBy(
            static fn (SkillGroup $group): string => $group->discipline->value,
        );

        $result = [];

        foreach (Discipline::cases() as $discipline) {
            $items = $groups->get($discipline->value);

            if ($items === null || $items->isEmpty()) {
                continue;
            }

            $result[] = [
                'discipline' => $discipline->value,
                'title' => $discipline->getLabel(),
                'tagline' => $discipline->tagline(),
                'groups' => $items->map(static fn (SkillGroup $group): array => [
                    'label' => $group->label,
                    'items' => array_values($group->items),
                ])->values()->all(),
            ];
        }

        return $result;
    }
}
