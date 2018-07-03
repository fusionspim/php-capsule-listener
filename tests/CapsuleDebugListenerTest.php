<?php
namespace FusionsPim\Tests\PhpCapsuleListener;

use FusionsPim\PhpCapsuleListener\CapsuleDebugListener;
use FusionsPim\Tests\PhpCapsuleListener\Models\{Author, Article};
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class CapsuleDebugListenerTest extends TestCase
{
    public function setUp()
    {
        Capsule::connection()->table('authors')->truncate();
        Capsule::connection()->table('articles')->truncate();
    }

    public function test_get_instance()
    {
        $instance = CapsuleDebugListener::getInstance();
        $this->assertInstanceOf(CapsuleDebugListener::class, $instance);
        $this->assertSame(CapsuleDebugListener::getInstance(), $instance);
    }

    public function test_get_named_instance()
    {
        $instance = CapsuleDebugListener::getInstance('write');
        $this->assertInstanceOf(CapsuleDebugListener::class, $instance);
        $this->assertSame(CapsuleDebugListener::getInstance('write'), $instance);
        $this->assertNotSame(CapsuleDebugListener::getInstance(), $instance);
    }
    public function test_log_queries()
    {
        $logs = [];
        CapsuleDebugListener::getInstance()->enable(function ($stack) use (&$logs) {
            $logs[] = $stack;
        });

        $author = Author::create([
            'name' => 'Arthur Dent',
            'email' => 'arthur@hitchhiker.co.uk',
        ]);

        $article = Article::create([
            'title'       => 'Always Bring a Towel!',
            'description' => 'When travelling through the galaxy, always bring a towel.',
            'tags'        => ['travel', 'towel'],
            'views'       => 42,
            'author_id'   => $author->id,
        ]);

        Author::find($author->id);
        Article::find($article->id);

        CapsuleDebugListener::getInstance()->disable();

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(FIXTURES_PATH . '/test_log_queries.json'),
            json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
