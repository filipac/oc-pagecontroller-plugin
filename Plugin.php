<?php namespace Filipac\PageController;

use System\Classes\PluginBase;
use Cms\Classes\Controller;
use Illuminate\Support\Str;
use October\Rain\Exception\ApplicationException;

class Plugin extends PluginBase
{
    public function boot()
    {
        \Event::listen('cms.page.init', function (Controller $controller) {
            $page = $controller->getPage();
            if ($page->controller) {
                if (!class_exists($page->controller)) {
                    throw new ApplicationException('Page controller "'.$page->controller.'" does not exist');
                }
                $c = app($page->controller, [$page, $controller->getLayout(), $controller]);
                if (!$c instanceof \Cms\Classes\CodeBase) {
                    throw new ApplicationException('Class "'.get_class($c).'" must extend "Cms\Classes\CodeBase" class in order to support page controller features.');
                }
                $ref = new \ReflectionClass($c);
                $publicMethods = $ref->getMethods(\ReflectionMethod::IS_PUBLIC);
                if (method_exists($c, 'onInit') && ($res = app()->call([$c, 'onInit']))) {
                    return $res;
                }
                foreach ($publicMethods as $method) {
                    if ($method->isConstructor() || $method->isDestructor()) {
                        continue;
                    }
                    $page->addDynamicMethod($method->name, function () use ($c, $method) {
                        return app()->call([$c, $method->name]);
                    });

                    $handlers = [
                        'onStart' => 'page.start',
                        'onEnd'   => 'page.end',
                    ];

                    if (array_key_exists($method->name, $handlers)) {
                        $controller->bindEvent($handlers[$method->name], function () use ($c, $method) {
                            return app()->call([$c, $method->name]);
                        });
                    } elseif (Str::startsWith($method->name, 'on')) {
                        $controller->bindEvent('ajax.beforeRunHandler', function ($handler) use ($controller, $method, $c) {
                            if ($handler == $method->name) {
                                $res = app()->call([$c, $method->name]);
                                return $res == null ? true : $res;
                            }
                        });
                    }
                }
            }
        });

        \Event::listen('cms.template.save', function (\Cms\Controllers\Index $controller, $templateObject, $type) {
            if ($userController = request()->get('controller')) {
                $templateObject->attributes['controller'] = $userController;
                $templateObject->save();
            }
        });

        \Event::listen('backend.form.extendFields', function ($widget) {
            if (
                !$widget->model instanceof \Cms\Classes\Page
            ) {
                return;
            }
            $widget->addTabFields([
                'controller' => [
                    'tab'   => 'Controller',
                    'label' => 'Controller class',
                ],
            ]);
        });
    }
}
