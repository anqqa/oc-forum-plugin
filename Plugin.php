<?php namespace Klubitus\Forum;

use Backend;
use System\Classes\PluginBase;

/**
 * Forum Plugin Information File
 */
class Plugin extends PluginBase {

    public $require = [
        'Klubitus.BBCode',
        'Klubitus.Search',
        'RainLab.User',
    ];


    /**
     * Returns information about this plugin.
     *
     * @return  array
     */
    public function pluginDetails() {
        return [
            'name'        => 'Klubitus Forum',
            'description' => 'Forums for Klubitus.',
            'author'      => 'Antti Qvickström',
            'icon'        => 'icon-comments',
            'homepage'    => 'https://github.com/anqqa/oc-forum-plugin',
        ];
    }

    /**
     * Registers any front-end components implemented in this plugin.
     *
     * @return  array
     */
    public function registerComponents() {
        return [
            'Klubitus\Forum\Components\Area'   => 'forumArea',
            'Klubitus\Forum\Components\Areas'  => 'forumAreas',
            'Klubitus\Forum\Components\Search' => 'forumSearch',
            'Klubitus\Forum\Components\Topic'  => 'forumTopic',
            'Klubitus\Forum\Components\Topics' => 'forumTopics',
        ];
    }


    public function registerSettings() {
        return [
            'settings' => [
                'label'       => 'Forum settings',
                'description' => 'Manage forum areas.',
                'category'    => 'Klubitus',
                'icon'        => 'icon-comments',
                'url'         => Backend::url('klubitus/forum/areas'),
                'order'       => 100,
            ]
        ];
    }

}
