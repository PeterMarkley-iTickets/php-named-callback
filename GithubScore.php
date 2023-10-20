<?php

require "vendor/autoload.php";

use Illuminate\Support\Collection;

class GitHubScore
{
    private $username;
    private $lookupScore;
	
    private function __construct($username)
    {
        $this->username = $username;
		
		$this->lookupScore = function($eventType)
		{
			debug_print_backtrace();
			return collect([
				'PushEvent' => 5,
				'CreateEvent' => 4,
				'IssuesEvent' => 3,
				'CommitCommentEvent' => 2,
			])->get($eventType, 1);
		};
    }
	
    public static function forUser($username)
    {
        return (new self($username))->score();
    }
	
    private function score()
    {
        return $this->events()->pluck('type')->map($this->lookupScore)->sum();
    }
	
    private function events()
    {
        //$url = "https://api.github.com/users/{$this->username}/events";
        return collect(json_decode('[{"type":"PushEvent"}]', true));
    }
}

var_dump(GitHubScore::forUser('PeterMarkley-iTickets'));
