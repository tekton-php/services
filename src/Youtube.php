<?php namespace Tekton\Services;

use Madcoda\Youtube as YoutubeAPI;
use DateTime;
use DateInterval;
use InvalidArgumentException;

class Youtube {

    use \Tekton\Support\Traits\LibraryWrapper;

    protected $config;

    function __construct(array $config = []) {
        $this->config = (object) $config;
        $this->library = new YoutubeAPI(array('key' => $this->config->key));
        $this->cache = app('cache');
    }

    function channel() {
        return $this->cache->remember('services.youtube.channel', $this->config->refresh, function()  {
            return $this->library->getChannelById($this->config->channel);
        });
    }

    function url() {
        return $this->config->url;
    }

    function videos($limit = 10) {
        if ((int) $limit > 50) {
            throw new InvalidArgumentException('Max 50 videos can be retrieved from YouTube in one request. You requested "'.$limit.'"');
        }

        // Transient::clear('services.youtube.videos');
        // Load videos from cache
        $videos = $this->cache->remember('services.youtube.videos', $this->config->refresh, function() {
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
            $videos = array();

            foreach ($result as $video) {
                $videos[] = $this->simplify($video);
            }

            return $videos;
        });

        // Only return the amount of videos request and not all in the cache
        if (count($videos) < $limit) {
            return $videos;
        }
        else {
            return array_slice($videos, 0, $limit);
        }
    }

    function uploads($limit = 10) {
        return $this->videos($limit);
    }

    function uploadsId() {
        $channel = $this->channel();
        return $channel->contentDetails->relatedPlaylists->uploads;
    }

    function refresh() {
        return $this->clear();
    }

    function clear() {
        $this->cache->forget('services.youtube.videos');
        $this->cache->forget('services.youtube.channel');
    }

    function simplify($video) {
        // Create duration string
        $start = new DateTime('@0'); // Unix epoch
        $start->add(new DateInterval($video->contentDetails->duration));
        $duration = $start->format('H:i:s');

        $date = new DateTime($video->snippet->publishedAt);
        $language = isset($video->snippet->defaultAudioLanguage) ? $video->snippet->defaultAudioLanguage : '';
        $tags = isset($video->snippet->tags) ? $video->snippet->tags : array();

        return (object) array(
            'thumb' => $video->snippet->thumbnails,
            'title' => $video->snippet->title,
            'tags' => $tags,
            'language' => $language,
            'url' => 'https://www.youtube.com/watch?v='.$video->id,
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

    function parse_id($url) {
        if (str_contains($url, 'http')) {
            return $this->library->parseVIdFromURL($url);
        }

        return esc_attr($url);
    }
}
