# PHP Named Callbacks

This is an academic excursion into the inner workings of PHP functions and variable types. It demonstrates the closest thing to a named callback function (i.e. not anonymous) that PHP allows.

## Background

In Adam Wathan's ebook [_Refactoring to Collections_](https://adamwathan.me/refactoring-to-collections/#packages) v1.1.0 page 85, he solves an example exercise in a way that caught my attention:

```
class GitHubScore
{
  // ...
  
  private function score()
  {
    return $this->events()->pluck('type')->map(function ($eventType) {
      return $this->lookupScore($eventType);
    })->sum();
  }
  
  private function lookupScore($eventType)
  {
    // ...
  }

  // ...
}
```

In particular, I noticed strong similarity in the function definitions between `lookupScore` and its no-op wrapper callback passed to `map`:
```
function ($eventType)
function lookupScore($eventType)
```

As someone whose first programming language was C, using [this](https://en.wikipedia.org/wiki/The_C_Programming_Language) as my textbook at the age of 12, I immediately thought: "Why the extra stack layer? Can't we just pass `lookupScore` directly as the parameter to `map`?"

## Other Languages

After all, with a little care in variable types, this is possible in C (and in fact required if you want to use callbacks at all):
```
#include <stdio.h>

void greet(char *greeting) {
  fprintf(stdout, "%s\n", greeting);
}

void execute_func(char *arg, void (*func)(char *)) {
  func(arg);
}

int main(int argc, char **argv) {
  execute_func("hello, world", greet);
  return 0;
}
```

It's even easier in JavaScript:
```
function greet(greeting) {
  alert(greeting);
}

function execute_func(arg, func) {
  func(arg);
}

execute_func("hello, world", greet);
```

Every major programming language I've seen allows some form of this. Surely PHP does too?

## The Solution

When I tried this with PHP 8, at first I couldn't find any syntax that would pass `lookupScore` as an argument without interpreting this as an expression to evaluate (causing `lookupScore` to execute before `map`). In PHP's official documentation, the terms "anonymous function" and "callback" [are treated as synonyms](https://www.php.net/manual/en/functions.anonymous.php), and [the Closure class](https://www.php.net/manual/en/class.closure.php) explicitly "disallows instantiation."

Eventually I did find a solution, which I have demonstrated in this code repo; but it's a bit strange and certainly does not improve Adam Wathan's example exercise.

First, `lookupScore` has to be declared as a class property instead of a method:
```
class GitHubScore
{
  private $lookupScore;
  // ...
```

Then, its function declaration has to be assigned at some point, e.g. in the constructor method:
```
  private function __construct($username)
  {
    // ...
    
    $this->lookupScore = function($eventType)
    {
      // ...
    };
  }
```

Now at last it can be passed as an argument in place of a closure:
```
  private function score()
  {
    return $this->events()->pluck('type')->map($this->lookupScore)->sum();
  }
```

In the eyes of the PHP engine, this is still an "anonymous function" even though it happens to be saved to a variable with a name. Doing this combines the advantages that we saw in C and Javascript:

1. There is no extra stack layer. A backtrace looks identical to how it would if we defined the instructions for `lookupScore` directly inside the call to `map`:
   ```
   #0 [internal function]: GitHubScore->{closure}('PushEvent', 0)
   #1 vendor/illuminate/collections/Arr.php(558): array_map(Object(Closure), Array, Array)
   #2 vendor/illuminate/collections/Collection.php(777): Illuminate\Support\Arr::map(Array, Object(Closure))
   #3 GithubScore.php(35): Illuminate\Support\Collection->map(Object(Closure))
   #4 GithubScore.php(30): GitHubScore->score()
   #5 GithubScore.php(45): GitHubScore::forUser('PeterMarkley-iT...')
   ```
2. Since these "anonymous" instructions are in fact saved to a variable, they could be reused just like a proper class method.

The disadvantage is, of course, that it flies in the face of both logic and common sense. While it greatly improves readability in the call to `map`, it does so at great cost in the definition of `lookupScore`.

Because of this, I can't immediately think of a real-world scenario where this would be beneficial to use ... but who knows? In any case, I'm satisfied just knowing that I can have that level of understanding and control over the call stack, like in other languages.
