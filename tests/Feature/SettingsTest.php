<?php

declare(strict_types=1);

use Backend\Widgets\Form;
use Illuminate\Support\Facades\Event;
use System\Controllers\Settings;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\FortifyEvent;
use Wobqqq\Fortify\Enums\WidgetItemColor;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\Fortify\Transformers\FortifyTransformer as CoreTransformer;
use Wobqqq\FortifyCsp\Services\CspService;

function cspSettingsForm(): Form
{
    $form = new Form(new Settings(), Fortify::instance());
    Event::dispatch('backend.form.extendFields', [$form]);

    return $form;
}

it('replaces the core placeholder with its own section on the settings form', function (): void {
    $form = cspSettingsForm();

    expect($form->tabFields)->toHaveKeys(['csp[section]', 'csp[cms_enabled]', 'csp[description]'])
        ->and($form->tabFields['csp[description]']['path'])->toBe('~/plugins/wobqqq/fortifycsp/controllers/fortify/_csp_description.htm')
        ->and(is_file(dirname(__DIR__, 2) . '/controllers/fortify/_csp_description.htm'))->toBeTrue();

    foreach (array_keys(CspService::DIRECTIVES) as $directive) {
        expect($form->tabFields)->toHaveKey(sprintf('csp[%s]', $directive));
    }
});

it('fills in the default policy on the form of a site that has none', function (): void {
    $model = cspSettingsForm()->model;

    expect($model instanceof Fortify ? $model->csp : null)->toMatchArray([
        'cms_enabled' => false,
        'cms_object_src' => [['v' => "'none'"]],
        'cms_frame_ancestors' => [['v' => "'none'"]],
    ]);
});

it('keeps the policy a site already has', function (): void {
    configureCsp(['cms_default_src' => [['v' => "'none'"]]]);

    $model = cspSettingsForm()->model;

    expect($model instanceof Fortify ? $model->csp : null)->toBe(['cms_enabled' => true, 'cms_default_src' => [['v' => "'none'"]]]);
});

it('adds the validation rules when the form is built', function (): void {
    $model = cspSettingsForm()->model;

    expect($model instanceof Fortify ? $model->rules : [])->toHaveKey('csp.cms_script_src.*.v');
});

it('shows on the dashboard whether a policy is sent', function (): void {
    $item = CoreTransformer::widgetGroupItemDto('placeholder');
    Event::dispatch(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_CSP->value, [&$item]);

    expect($item)->toBeInstanceOf(WidgetGroupItemDto::class)
        ->and($item->color)->toBe(WidgetItemColor::DANGER);

    configureCsp(['cms_default_src' => [['v' => "'self'"]]]);
    Event::dispatch(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_CSP->value, [&$item]);

    expect($item->color)->toBe(WidgetItemColor::SUCCESS);
});
