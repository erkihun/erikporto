<?php

declare(strict_types=1);

namespace App\Queries;

use App\Enums\Discipline;
use App\Enums\InquiryType;
use App\Http\Resources\ExperienceResource;
use App\Http\Resources\ProfileResource;
use App\Http\Resources\ProjectSummaryResource;
use App\Models\Experience;
use App\Models\ProcessStep;
use App\Models\Profile;
use App\Models\Project;
use App\Models\SkillGroup;
use App\Support\PortfolioCache;
use Illuminate\Http\Request;

/**
 * Assembles every prop the home page needs in a fixed number of queries,
 * resolved to plain arrays so the result is cacheable.
 */
final readonly class HomePageQuery
{
    public function __construct(private Request $request) {}

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

        return [
            'profile' => (new ProfileResource($profile))->resolve($this->request),
            'projects' => ProjectSummaryResource::collection($projects)->resolve($this->request),
            'capabilities' => $this->capabilities(),
            'process' => ProcessStep::query()->ordered()->get(['id', 'title', 'description'])->toArray(),
            'experience' => ExperienceResource::collection(Experience::query()->ordered()->get())->resolve($this->request),
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
