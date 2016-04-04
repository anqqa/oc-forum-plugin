<?php namespace Klubitus\Forum\Components;

use Auth;
use Cms\Classes\Page;
use Klubitus\Forum\Models\Area as AreaModel;
use Cms\Classes\ComponentBase;
use Klubitus\Forum\Models\Topic as TopicModel;
use October\Rain\Exception\ApplicationException;
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

        $this->area = AreaModel::findOrFail((int)$this->property('id'));

        if ($this->area->is_private && !Auth::check()) {
            throw new ApplicationException('Authenticated users only');
        }

        return $this->area;
    }


    public function getPropertyOptions($property) {
        return ['' => '- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }


    public function onRun() {
        $this->prepareVars();

        $this->page['area'] = $this->getArea();
        $this->page['title'] = $this->getArea()->name;

        return $this->prepareTopics();
    }


    protected function prepareTopics() {
        if ($area = $this->getArea()) {
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
                $query = [];
                $search and $query['search'] = $search;
                $query['page'] = '';

                $paginationUrl = Request::url() . '?' . http_build_query($query);

                if ($currentPage > ($lastPage = $topics->lastPage()) && $currentPage > 1) {
                    return Redirect::to($paginationUrl . $lastPage);
                }

                $this->page['paginationUrl'] = $paginationUrl;
            }
        }
    }


    protected function prepareVars() {
        $this->topicPage = $this->page['topicPage'] = $this->property('topicPage');
    }

}
