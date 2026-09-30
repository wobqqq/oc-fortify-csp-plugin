<?php

declare(strict_types=1);

namespace Wobqqq\FortifyCsp\Listeners;

use Backend;
use Backend\Widgets\Form;
use October\Rain\Events\Dispatcher;
use System\Controllers\Settings;
use Wobqqq\Fortify\Dto\WidgetGroupItemDto;
use Wobqqq\Fortify\Enums\FortifyEvent;
use Wobqqq\Fortify\Enums\WidgetItemColor;
use Wobqqq\Fortify\Models\Fortify;
use Wobqqq\Fortify\Transformers\FortifyTransformer;
use Wobqqq\FortifyCsp\Cache\CspDtoCache;
use Wobqqq\FortifyCsp\Instances\CspDtoInstance;
use Wobqqq\FortifyCsp\Services\CspService;

final readonly class FortifyListener
{
    public function __construct(
        private CspDtoCache $cspDtoCache,
    ) {
    }

    /**
     * @param Dispatcher $event
     */
    public function subscribe($event): void
    {
        $event->listen(FortifyEvent::SERVICES_WIDGET_GROUP_ITEM_CSP->value, function (WidgetGroupItemDto &$widgetGroupItemDto): void {
            $this->serveWidgetGroupItem($widgetGroupItemDto);
        });

        $event->listen(FortifyEvent::MODEL_FORTIFY_INIT_SETTINGS_DATA->value, function (Fortify &$fortify): void {
            $this->serveModelInitSettingsData($fortify);
        });

        Fortify::extend(function (Fortify $fortify): void {
            $this->serveModel($fortify);
        });

        // Model events, not bindEvent(): the settings instance may predate this listener.
        $event->listen(
            ['eloquent.saved: ' . Fortify::class, 'eloquent.deleted: ' . Fortify::class],
            function (): void {
                $this->cspDtoCache->clear();
            },
        );

        $event->listen('backend.form.extendFields', function (Form $form): void {
            if (!$form->getController() instanceof Settings || !$form->model instanceof Fortify || $form->isNested) {
                return;
            }

            $fortify = $form->model;

            $this->serveModel($fortify);
            $this->serveModelInitSettingsData($fortify);
            $this->serveFields($form);
        });
    }

    private function serveWidgetGroupItem(WidgetGroupItemDto &$widgetGroupItemDto): void
    {
        $settingsLink = FortifyTransformer::widgetItemLinkDto(
            'wobqqq.fortify::lang.buttons.edit',
            Backend::url('system/settings/update/wobqqq/fortify/fortify#primarytab-csp'),
            'icon-wrench',
        );
        $cspDto = CspDtoInstance::instance()->get();
        $color = $cspDto->cmsEnabled && $cspDto->cmsHeaderValue !== null
            ? WidgetItemColor::SUCCESS
            : WidgetItemColor::DANGER;
        $widgetGroupItemDto = FortifyTransformer::widgetGroupItemDto(
            'wobqqq.fortify::lang.fields.csp',
            [$settingsLink],
            $color,
            'icon-lock',
        );
    }

    private function serveModelInitSettingsData(Fortify $fortify): void
    {
        $csp = is_array($fortify->csp) ? $fortify->csp : [];

        if ($csp !== []) {
            return;
        }

        $csp['cms_enabled'] = false;
        $csp['cms_default_src'] = [['v' => "'self'"]];
        $csp['cms_script_src'] = [['v' => "'self'"], ['v' => "'unsafe-inline'"], ['v' => 'https:']];
        $csp['cms_style_src'] = [['v' => "'self'"], ['v' => "'unsafe-inline'"], ['v' => 'https:']];
        $csp['cms_img_src'] = [['v' => "'self'"], ['v' => 'data:'], ['v' => 'blob:'], ['v' => 'https:']];
        $csp['cms_font_src'] = [['v' => "'self'"], ['v' => 'data:'], ['v' => 'https:']];
        $csp['cms_connect_src'] = [['v' => "'self'"], ['v' => 'https:']];
        $csp['cms_media_src'] = [['v' => "'self'"], ['v' => 'blob:'], ['v' => 'https:']];
        $csp['cms_frame_src'] = [['v' => "'self'"], ['v' => 'https:']];
        $csp['cms_object_src'] = [['v' => "'none'"]];
        $csp['cms_base_uri'] = [['v' => "'self'"]];
        $csp['cms_form_action'] = [['v' => "'self'"]];
        $csp['cms_frame_ancestors'] = [['v' => "'none'"]];

        $fortify->csp = $csp;
    }

    private function serveModel(Fortify $fortify): void
    {
        foreach (CspService::DIRECTIVES as $cspDirectiveKey => $cspDirective) {
            $fortify->attributeNames[sprintf('csp.%s.*.v', $cspDirectiveKey)] = 'wobqqq.fortify::lang.fields.value';

            $fortify->rules[sprintf('csp.%s.*.v', $cspDirectiveKey)] = ['nullable', 'string', 'max:100', 'regex:' . CspService::SOURCE_PATTERN];
            $fortify->rules[sprintf('csp.%s', $cspDirectiveKey)] = 'nullable|array|max:150';
        }
    }

    private function serveFields(Form $form): void
    {
        $form->removeField('csp[section]');
        $form->removeField('csp[plugin]');

        $fields = [
            'csp[section]' => [
                'label' => 'wobqqq.fortify::lang.fields.csp',
                'type' => 'section',
                'span' => 'full',
                'tab' => 'wobqqq.fortify::lang.tabs.csp',
            ],

            'csp[cms_enabled]' => [
                'label' => 'wobqqq.fortify::lang.fields.enabled',
                'span' => 'full',
                'type' => 'switch',
                'tab' => 'wobqqq.fortify::lang.tabs.csp',
                'default' => false,
                'comment' => 'wobqqq.fortify::lang.comments.csp_cms_enabled',
                'commentHtml' => true,
            ],

            'csp[description]' => [
                'label' => ' ',
                'span' => 'full',
                'type' => 'partial',
                'path' => '~/plugins/wobqqq/fortifycsp/controllers/fortify/_csp_description.htm',
                'tab' => 'wobqqq.fortify::lang.tabs.csp',
                'disabled' => true,
                'trigger' => [
                    'action' => 'show',
                    'field' => 'csp[cms_enabled]',
                    'condition' => 'checked',
                ],
            ],
        ];

        foreach (CspService::DIRECTIVES as $cspDirectiveKey => $cspDirective) {
            $fields[sprintf('csp[%s]', $cspDirectiveKey)] = [
               'label' => $cspDirective,
               'type' => 'datatable',
               'span' => 'auto',
               'tab' => 'wobqqq.fortify::lang.tabs.csp',
               'adding' => true,
               'deleting' => true,
               'searching' => false,
               'recordsPerPage' => 15,
               'trigger' => [
                   'action' => 'show',
                   'field' => 'csp[cms_enabled]',
                   'condition' => 'checked',
               ],
               'columns' => [
                   'v' => [
                       'type' => 'string',
                       'title' => 'wobqqq.fortify::lang.fields.value',
                   ],
               ],
            ];
        }

        $form->addTabFields($fields);
    }
}
