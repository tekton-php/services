<?php namespace Tekton\Services;

use Madcoda\Youtube\Youtube as YoutubeAPI;
use DateTime;
use DateInterval;
use InvalidArgumentException;
use Exception;
use Tekton\Support\Repository;
use Illuminate\Cache\CacheManager;

class Youtube
{
    use \Tekton\Support\Traits\LibraryWrapper;

    protected $config;

    public function __construct(array $config = [], CacheManager $cache)
    {
        $this->config = new Repository($config);
        $this->library = new YoutubeAPI(array('key' => $this->config->get('key')));
        $this->cache = $cache;
    }

    public function channel()
    {
        // $this->cache->forget('services.youtube.channel');

        return $this->cache->remember('services.youtube.channel', $this->config->get('refresh'), function() {
            try {
                return $this->library->getChannelById($this->config->get('channel'));
            }
            catch (Exception $e) {
                return '';
            }
        });
    }

    public function url()
    {
        return $this->config->get('url');
    }

    public function videos($limit = 10)
    {
        if ((int) $limit > 50) {
            throw new InvalidArgumentException('Max 50 videos can be retrieved from YouTube in one request. You requested "'.$limit.'"');
        }

        // $this->cache->forget('services.youtube.videos');

        // Load videos from cache
        $videos = $this->cache->remember('services.youtube.videos', $this->config->get('refresh'), function() {
            try {
                // Get playlist
                $playlist = $this->library->getPlaylistItemsByPlaylistIdAdvanced(array(
                    'playlistId' => $this->uploadsId(),
                    'maxResults' => 50,
                    'part' => 'contentDetails',
                ));

                // Abort if the "uploads" playlist isn't accessible
                if (empty($playlist)) {
                    return array();
                }

                // Create a request to find information for each video
                $ids = array();

                foreach ($playlist as $video) {
                    $ids[] = $video->contentDetails->videoId;
                }

                // Create a simpler object to work with
                $result = $this->library->getVideosInfo($ids);
                $videos = [];

                foreach ($result as $video) {
                    $videos[] = $this->simplify($video);
                }

                return $videos;
            }
            catch (Exception $e) {
                return [];
            }
        });

        // Only return the amount of videos request and not all in the cache
        if (count($videos) < $limit) {
            return $videos;
        }
        else {
            return array_slice($videos, 0, $limit);
        }
    }

    public function uploads($limit = 10)
    {
        return $this->videos($limit);
    }

    public function uploadsId()
    {
        $channel = $this->channel();
        return (empty($channel)) ? '' : $channel->contentDetails->relatedPlaylists->uploads;
    }

    public function refresh()
    {
        return $this->clear();
    }

    public function clear()
    {
        $this->cache->forget('services.youtube.videos');
        $this->cache->forget('services.youtube.channel');
    }

    public function simplify($video)
    {
        // Create duration string
        $start = new DateTime('@0'); // Unix epoch
        $start->add(new DateInterval($video->contentDetails->duration));
        $duration = $start->format('H:i:s');

        $date = new DateTime($video->snippet->publishedAt);
        $language = isset($video->snippet->defaultAudioLanguage) ? $video->snippet->defaultAudioLanguage : '';
        $tags = isset($video->snippet->tags) ? $video->snippet->tags : [];
        $domain = $this->config->get('cookie', true) ? 'www.youtube.com' : 'www.youtube-nocookie.com';

        return (object) array(
            'id' => $video->id,
            'thumb' => $video->snippet->thumbnails,
            'title' => $video->snippet->title,
            'tags' => $tags,
            'language' => $language,
            'url' => $this->getUrl($video->id),
            'embed' => $this->getEmbedUrl($video->id),
            'duration' => $duration,
            'date' => $date,
            'views' => $video->statistics->viewCount,
            'likes' => $video->statistics->likeCount,
            'dislikes' => $video->statistics->dislikeCount,
            'favorites' => $video->statistics->favoriteCount,
            'comments' => $video->statistics->commentCount,
            'description' => $video->snippet->description,
        );
    }

    public function getUrl($id)
    {
        return 'https://www.youtube.com/watch?='.$this->extractVideoId($id);
    }

    public function getEmbedUrl($id)
    {
        $domain = ($this->config->get('cookie', true)) ? 'youtube' : 'youtube-nocookie';
        $related = ($this->config->get('related', false)) ? '?rel=0' : '';

        return 'https://www.'.$domain.'.com/embed/'.$this->extractVideoId($id).$related;
    }

    public function extractVideoId($url)
    {
        if (str_contains($url, 'http')) {
            return $this->library->parseVIdFromURL($url);
        }

        return $url;
    }
}
