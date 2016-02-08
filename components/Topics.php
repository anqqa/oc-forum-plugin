<?php namespace Klubitus\Forum\Components;

use Cms\Classes\ComponentBase;
use Cms\Classes\Page;
use Klubitus\Forum\Models\Topic as TopicModel;
use Lang;
use October\Rain\Support\Collection;


class Topics extends ComponentBase {

    const HOT_TOPICS = 'hot_topics';
    const NEW_POSTS  = 'new_posts';
    const NEW_TOPICS = 'new_topics';

    /**
     * @var  string
     */
    public $listType;

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
            'name'        => 'Forum Topics',
            'description' => 'Simple topic list.'
        ];
    }


    public function defineProperties() {
        return [
            'topicPage' => [
                'title'       => 'Topic Page',
                'description' => 'Page name for a single forum topic.',
                'type'        => 'dropdown',
            ],
            'listType' => [
                'title'       => 'Topic List Type',
                'description' => 'Type of topic to list.',
                'type'        => 'dropdown',
                'default'     => self::NEW_POSTS,
                'options'     => [
                    self::HOT_TOPICS => 'Hot topics',
                    self::NEW_POSTS  => 'New posts',
                    self::NEW_TOPICS => 'New topics',
                ],
            ],
        ];
    }


    public function getTitle() {
        switch ($this->listType) {
            case self::HOT_TOPICS: return Lang::get('klubitus.forum::lang.topics.hot_topics');
            case self::NEW_POSTS: return Lang::get('klubitus.forum::lang.topics.new_posts');
            case self::NEW_TOPICS: return Lang::get('klubitus.forum::lang.topics.new_topics');
        }

        return '';
    }


    public function getTopicPageOptions() {
        return ['' => '- none -'] + Page::sortBy('baseFileName')->lists('baseFileName', 'baseFileName');
    }


    public function listTopics() {
        if (!is_null($this->topics)) {
            return $this->topics;
        }

        /** @var  Collection  $topics */
        switch ($this->listType) {
            case self::NEW_POSTS:
                $topics = TopicModel::recentPosts()->limit(10)->get();
                break;

            case self::NEW_TOPICS:
                $topics = TopicModel::recentTopics()->limit(10)->get();
                break;

            case self::HOT_TOPICS:
            default:
                return [];
        }

        $topics->each(function(TopicModel $topic) {
            $topic->setUrl($this->topicPage, $this->controller);
        });

        return $topics;
    }


    public function onRun() {
        $this->prepareVars();

        $this->title  = $this->getTitle();
        $this->topics = $this->listTopics();
    }


    protected function prepareVars() {
        $this->listType  = $this->property('listType');
        $this->topicPage = $this->property('topicPage');
    }

}
