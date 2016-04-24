<?php namespace Klubitus\Forum\Models;

use Auth;
use Db;
use Klubitus\Forum\Classes\Search;
use Model;
use October\Rain\Database\QueryBuilder;
use October\Rain\Database\Traits\Validation;
use RainLab\User\Models\User;


/**
 * Post Model
 */
class Post extends Model {
    use Validation;


    /**
     * @var  string  The database table used by the model.
     */
    public $table = 'forum_posts';

    /**
     * @var  array  Guarded fields.
     */
    protected $guarded = [];

    /**
     * @var  array  Fillable fields.
     */
    protected $fillable = ['post'];

    /**
     * @var  array  The attributes visible in arrays.
     */
    protected $visible = ['post', 'author', 'topic'];

    /**
     * @var  array  Validation rules.
     */
    public $rules = [
        'forum_topic_id' => 'required',
        'author_id'      => 'required',
        'post'           => 'required',
    ];

    /**
     * @var  array  Relations.
     */
    public $belongsTo = [
        'topic'  => ['Klubitus\Forum\Models\Topic', 'key' => 'forum_topic_id'],
        'author' => 'RainLab\User\Models\User',
    ];


    public function afterCreate() {
        $this->topic->post_count++;
        $this->topic->last_post_at = $this->created_at;
        $this->topic->last_post = $this;
        $this->topic->last_poster = $this->author;
        $this->topic->save();

        $this->topic->area->increment('post_count');
    }


    public function afterDelete() {
        $this->topic->decrement('post_count');
        $this->topic->area->decrement('post_count');

        // Delete also topic if it doesn't contain any more posts
//        if ($this->topic->post_count <= 0) {
//            $this->topic->delete();
//        }
    }


    public function canEdit(User $user = null) {
        if (!$user) {
            $user = Auth::getUser();
        }

        if (!$user) {
            return false;
        }

        return $this->author_id == $user->id;
    }


    /**
     * Create a post in a topic.
     *
     * @param  Topic  $topic
     * @param  User   $user
     * @param  $post
     * @return  Post
     */
    public static function createInTopic(Topic $topic, User $user, $post) {
        $post = new static;
        $post->topic = $topic;
        $post->author = $user;
        $post->post = $post;
        $post->save();

        return $post;
    }


    /**
     * Filter topic.
     *
     * @param   QueryBuilder  $query
     * @param   int           $areaId
     * @return  QueryBuilder
     */
    public function scopeFilterArea($query, $areaId) {
        return $query->where('forum_area_id', $areaId);
    }


    /**
     * Filter topic.
     *
     * @param   QueryBuilder  $query
     * @param   int           $topicId
     * @return  QueryBuilder
     */
    public function scopeFilterTopic($query, $topicId) {
        return $query->where('forum_topic_id', $topicId);
    }


    /**
     * Get topics with latest replies.
     *
     * @param   QueryBuilder  $query
     * @return  QueryBuilder
     */
    public function scopeRecentPosts($query) {
        return $query->orderBy('created_at', 'asc');
    }


    /**
     * Search topics and posts.
     *
     * @param   QueryBuilder  $query
     * @param   string        $search
     * @return  QueryBuilder
     */
    public function scopeSearch($query, $search) {
        $parsed = Search::parseQuery(
            $search,
            ['post'],
            ['post' => 'post', 'author' => 'author', 'by' => 'author']
        );

        // Search authors
        $authors = !empty($parsed['author'])
            ? User::whereIn(DB::raw('LOWER(username)'), $parsed['author'])
                ->lists('id')
            : [];
        if ($authors) {
            $query->whereIn('author_id', $authors);
        }

        // Search posts
        if (!empty($parsed['post'])) {
            $query->searchWhere(implode(' ', $parsed['post']), 'post');
        }


        return $query;
    }

}
