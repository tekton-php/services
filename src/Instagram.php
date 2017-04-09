<?php namespace Tekton\Services;

use Vinkla\Instagram\Instagram as InstagramAPI;
use DateTime;
use InvalidArgumentException;
use Exception;

class Instagram {

    use \Tekton\Support\Traits\LibraryWrapper;

    protected $api;
    protected $config;
    protected $videos = array();

    function __construct(array $config = []) {
        $this->config = (object) $config;
        $this->library = new InstagramAPI();
        $this->cache = app('cache');
    }

    function url() {
        return $this->config->url;
    }

    function user() {
        return $this->config->user;
    }

    function images($limit = 10) {
        if ((int) $limit > 50) {
            throw new InvalidArgumentException('Max 50 images can be retrieved from Instagram in one request. You requested "'.$limit.'"');
        }

        // $this->cache->forget('services.instagram.images');

        // Load videos from cache
        $images = $this->cache->remember('services.instagram.images', $this->config->refresh, function() {
            // Try and get images from Instagram
            try {
                $result = $this->library->get($this->config->user);
                $images = [];

                // Process result
                foreach ($result as $image) {
                    $images[] = $this->simplify($image);
                }

                // Return result
                return $images;
            }
            catch (Exception $e) {
                return [];
            }
        });

        // Only return the amount of images requested and not all in the cache
        if (count($images) < $limit) {
            return $images;
        }
        else {
            return array_slice($images, 0, $limit);
        }
    }

    function refresh() {
        $this->clear();
    }

    function clear() {
        $this->cache->forget('services.instagram.images');
    }

    function simplify($image) {
        $date = new DateTime();
        $date->setTimestamp($image['created_time']);
        $resolutions = array();

        foreach ($image['images'] as $key => $val) {
            $resolutions[$key] = (object) $val;
        }

        return (object) array_merge($resolutions, array(
            'caption' => (object) [
                'raw' => $image['caption']['text'],
                'html' => $this->parse_hashtags(str_make_links($image['caption']['text'])),
            ],
            'url' => $image['link'],
            'likes' => $image['likes']['count'],
            'date' => $date,
            'type' => $image['type'],
            'user' => (object) array(
                'thumb' => $image['user']['profile_picture'],
                'id' => $image['user']['id'],
                'name' => $image['user']['full_name'],
            ),
        ));
    }

    function parse_hashtags($text) {
        return preg_replace('/(\#)([^\s]+)/', ' <a href="https://www.instagram.com/explore/tags/$2">#$2</a> ', $text);
    }
}
