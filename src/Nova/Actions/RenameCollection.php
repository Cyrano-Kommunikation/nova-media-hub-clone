<?php

namespace Cyrano\MediaHub\Nova\Actions;


use Illuminate\Bus\Queueable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Queue\InteractsWithQueue;
use Laravel\Nova\Actions\Action;
use Laravel\Nova\Fields\ActionFields;
use Laravel\Nova\Fields\Text;
use Laravel\Nova\Http\Requests\NovaRequest;

class RenameCollection extends Action
{
    use InteractsWithQueue, Queueable;

    public $name = "Kollektion umbenennen";

    public function handle(ActionFields $fields, Collection $models): void
    {

    }

    public function fields(NovaRequest $request): array
    {
        return [
            Text::make('Name'),
        ];
    }
}
