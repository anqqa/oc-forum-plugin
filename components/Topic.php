<?php namespace Klubitus\Forum\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Klubitus\Forum\Models\Area as AreaModel;
use Klubitus\Forum\Models\Post as PostModel;
use Klubitus\Forum\Models\Topic as TopicModel;
use Klubitus\Search\Classes\Search;
use October\Rain\Exception\ApplicationException;
use October\Rain\Support\Collection;
use Redirect;
use Request;


class Topic extends ComponentBase {

    /**
     * @var  AreaModel
     */
    protected $area;

    /**
     * @var  string
     */
    public $areaPage;

    /**
     * @var  Collection  PostModel
     */
    public $posts = null;

    /**
     * @var  string URL to redirect after posting.
     */
    public $returnUrl;

    /**
     * @var  TopicModel
     */
    protected $topic;


    public function componentDetails() {
        return [
            'name'        => 'Forum Topic',
            'description' => 'Single forum topic with posts.'
        ];
    }


    public function defineProperties() {
        return [
            'id' => [
                'title'   => 'Topic Id',
                'default' => '{{ :topic_id }}',
                'type'    => 'string',
            ],
            'areaPage' => [
                'title'       => 'Area Page',
                'description' => 'Page name for a single forum area.',
                'type'        => 'dropdown',
            ],
        ];
    }


    public function getArea() {
        if (!is_null($this->area)) {
            return $this->area;
        }

        if ($topic = $this->getTopic()) {
            $area = $topic->area;
        }
        else if ($areaId = input('area')) {
            $area = AreaModel::findOrFail($areaId);
        }
        else {
            $area = null;
        }

        if ($area) {
            if ($area->is_private && !Auth::check()) {
                throw new ApplicationException('Authenticated users only');
            }

            $area->setUrl($this->areaPage, $this->controller);
        }

        return $this->area = $area;
    }


    public function getPropertyOptions($property) {
        return ['' => '- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }


    public function getTopic() {
        if (!is_null($this->topic)) {
            return $this->topic;
        }

        /** @var  TopicModel  $topic */
        $topic = TopicModel::findOrFail((int)$this->property('id'));

        if ($topic) {
            $topic->increaseReadCount();
        }

        return $this->topic = $topic;
    }


    public function onRun() {
        $this->prepareVars();

        $this->page['area'] = $this->getArea();
        $this->page['topic'] = $this->getTopic();
        $this->page['title'] = $this->getTopic()->name;

        return $this->preparePosts();
    }


    protected function preparePosts() {
        if ($topic = $this->getTopic()) {
            $currentPage = input('page');
            $search = trim(input('search'));

            /** @var  Collection  $posts */
            $posts = PostModel::with('author')
                ->filterTopic($topic->id)
                ->recentPosts()
                ->search($search)
                ->paginate(20, $currentPage);

            $this->page['posts'] = $this->posts = $posts;

            // Pagination
            $query = ['page' => ''];

            if ($search) {
                $query['search'] = $search;
            }

            $paginationUrl = Request::url() . '?' . http_build_query($query);

            $lastPage = $posts->lastPage();
            if ($currentPage == 'last' || ($currentPage > $lastPage && $currentPage > 1)) {
                return Redirect::to($paginationUrl . $lastPage);
            }

            $this->page['paginationUrl'] = $paginationUrl;
        }

        // Return url
        if ($this->getArea()) {
            $this->returnUrl = $this->page['returnUrl'] = $this->area->url;
        }

    }


    protected function prepareVars() {
        $this->areaPage = $this->page['areaPage'] = $this->property('areaPage');
 
        if ($highlight = Search::parseQuery(
            trim(input('search')),
            ['post'], ['post' => 'post']
        )) {
            $this->page['highlight'] = $highlight['post'];
        }
    }
    
}
