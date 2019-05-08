<?php
namespace FusionsPim\Tests\PhpCapsuleListener;

use FusionsPim\PhpCapsuleListener\CapsuleDebugListener;
use FusionsPim\Tests\PhpCapsuleListener\Models\{Article, Author};
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

    public function test_set_connection()
    {
        $defaultInstance = CapsuleDebugListener::getInstance()->setConnection(Capsule::connection());
        $this->assertSame('default', $defaultInstance->getConnection()->getName());

        $otherInstance = CapsuleDebugListener::getInstance()->setConnection(Capsule::connection('other'));
        $this->assertSame('other', $otherInstance->getConnection()->getName());
    }

    public function test_dump_queries()
    {
        $this->markTestIncomplete('Need to figure out how to capture VarDumper output to a buffer');
    }

    public function test_log_queries()
    {
        start_capturing_queries();

        $author = Author::create([
            'name'  => 'Arthur Dent',
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

        $logs = stop_capturing_queries();

        $this->assertJsonStringEqualsJsonString(
            file_get_contents(FIXTURES_PATH . '/test_log_queries.json'),
            json_encode($logs, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES)
        );
    }
}
