<?php namespace Klubitus\Forum\Components;

use Cms\Classes\Page;
use Db;
use Cms\Classes\ComponentBase;
use Klubitus\Forum\Models\Area as AreaModel;
use Klubitus\Forum\Models\Topic as TopicModel;
use Klubitus\Search\Classes\Search as SearchHelper;
use October\Rain\Support\Collection;
use Redirect;
use Request;


class Search extends ComponentBase {

    /**
     * @var  string
     */
    public $areaPage;

    /**
     * @var  string
     */
    public $topicPage;

    /**
     * @var  Collection
     */
    public $results = null;


    public function componentDetails() {
        return [
            'name'        => 'Forum Search',
            'description' => 'Full forum search results.'
        ];
    }


    public function defineProperties() {
        return [
            'areaPage' => [
                'title'       => 'Area Page',
                'description' => 'Page name for a single forum area.',
                'type'        => 'dropdown',
            ],
            'topicPage' => [
                'title'       => 'Topic Page',
                'description' => 'Page name for a single forum topic.',
                'type'        => 'dropdown',
            ],
        ];
    }


    public function getPropertyOptions($property) {
        return ['' => '- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }


    public function onRun() {

        // Skip if not searching
        if (!trim(input('search'))) {
            return;
        }

        $this->prepareVars();

        return $this->prepareResults();
    }


    protected function prepareResults() {
        $currentPage = input('page');
        $search = trim(input('search'));

        /** @var  Collection  $topics */
        $topics = TopicModel::filterAreas(AreaModel::getAccessibleIds())
            ->with('area')
            ->recentPosts()
            ->search($search, false)
            ->paginate(20, $currentPage);

        // Add url
        $postSearchTokens = SearchHelper::parseQuery(
            $search,
            ['post'], ['post' => 'post', 'author' => 'author', 'by' => 'author']
        );
        $postSearch = SearchHelper::buildQuery($postSearchTokens, ['post']);
        $topics->each(function(TopicModel $topic) use ($postSearch) {
            $topic->setUrl(
                $this->topicPage,
                $this->controller,
                $postSearch ? ['search' => $postSearch] : null
            );
            $topic->area->setUrl(
                $this->areaPage,
                $this->controller
            );
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


    protected function prepareVars() {
        $this->areaPage  = $this->page['areaPage']  = $this->property('areaPage');
        $this->topicPage = $this->page['topicPage'] = $this->property('topicPage');

        if ($highlight = SearchHelper::parseQuery(
            trim(input('search')),
            ['topic'], ['topic' => 'topic']
        )) {
            $this->page['highlight'] = $highlight['topic'];
        }
    }

}
