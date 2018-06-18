<?php namespace Tekton\Services;

use Vinkla\Instagram\Instagram as InstagramAPI;
use DateTime;
use InvalidArgumentException;
use Exception;
use Tekton\Support\Repository;
use Illuminate\Cache\CacheManager;

class Instagram
{
    use \Tekton\Support\Traits\LibraryWrapper;

    protected $api;
    protected $config;
    protected $videos = array();

    public function __construct(array $config = [], CacheManager $cache)
    {
        $this->config = new Repository($config);
        $this->cache = $cache;
        $this->library = new InstagramAPI($this->config->get('token'));
    }

    public function url()
    {
        return $this->config->get('url');
    }

    public function user()
    {
        return $this->config->get('user');
    }

    public function images($limit = 10)
    {
        if ((int) $limit > 20) {
            throw new InvalidArgumentException('Max 50 images can be retrieved from Instagram in one request. You requested "'.$limit.'"');
        }

        // $this->cache->forget('services.instagram.images');

        // Load videos from cache
        $images = $this->cache->remember('services.instagram.images', $this->config->get('refresh'), function() {
            // Try and get images from Instagram
            try {
                $result = $this->library->get();
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

    public function refresh()
    {
        $this->clear();
    }

    public function clear()
    {
        $this->cache->forget('services.instagram.images');
    }

    public function simplify($image)
    {
        $date = new DateTime();
        $date->setTimestamp($image->created_time);

        $image->date = $date;
        $image->caption->html = $this->parseHashtags(str_make_links($image->caption->text));
        $image->url = $image->link;

        return $image;
    }

    public function parseHashtags($text)
    {
        return preg_replace('/(\#)([^\s]+)/', ' <a href="https://www.instagram.com/explore/tags/$2">#$2</a> ', $text);
    }
}
