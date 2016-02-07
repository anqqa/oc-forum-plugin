<?php namespace Klubitus\Forum\Components;

use Cms\Classes\Page;
use Klubitus\Forum\Models\Area as AreaModel;
use Cms\Classes\ComponentBase;
use Klubitus\Forum\Models\Topic as TopicModel;
use October\Rain\Support\Collection;
use Redirect;
use Request;


class Area extends ComponentBase {

    /**
     * @var  AreaModel
     */
    protected $area;

    /**
     * @var  string
     */
    public $topicPage;

    /**
     * @var  Collection  TopicModels
     */
    public $topics = null;


    public function componentDetails() {
        return [
            'name'        => 'Forum Area',
            'description' => 'Single forum area with topics.'
        ];
    }


    public function defineProperties() {
        return [
            'id' => [
                'title'   => 'Area Id',
                'default' => '{{ :area_id }}',
                'type'    => 'string',
            ],
            'topicPage' => [
                'title'       => 'Topic Page',
                'description' => 'Page name for a single forum topic.',
                'type'        => 'dropdown',
            ],
        ];
    }


    public function getArea() {
        if (!is_null($this->area)) {
            return $this->area;
        }

        return $this->area = AreaModel::findOrFail((int)$this->property('id'));
    }


    public function getPropertyOptions($property) {
        return ['' => '- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }


    public function onRun() {
        $this->prepareVars();

        $this->page['area'] = $this->getArea();

        return $this->prepareTopics();
    }


    protected function prepareTopics() {
        $this->topicPage = $this->page['topicPage'] = $this->property('topicPage');
    }


    protected function prepareVars() {
        if ($area = $this->getArea()) {

            // Load topics
            $currentPage = input('page');
            $search = trim(input('search'));

            /** @var  Collection  $topics */
            $topics = TopicModel::areas($area->id)
                ->recentPosts()
                ->search($search)
                ->paginate(20, $currentPage);

            // Add url
            $topics->each(function(TopicModel $topic) {
                $topic->setUrl($this->topicPage, $this->controller);
            });

            $this->page['topics'] = $this->topics = $topics;

            // Paginate
            if ($topics) {
                $query = ['page' => ''];

                if ($search) {
                    $query['search'] = $search;
                }

                $paginationUrl = Request::url() . '?' . http_build_query($query);

                if ($currentPage > ($lastPage = $topics->lastPage()) && $currentPage > 1) {
                    return Redirect::to($paginationUrl . $lastPage);
                }

                $this->page['paginationUrl'] = $paginationUrl;
            }
        }
    }
}
