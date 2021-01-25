<?php
namespace FusionsPim\Tests\PhpCapsuleListener;

use FusionsPim\PhpCapsuleListener\CapsuleDebugListener;
use FusionsPim\Tests\PhpCapsuleListener\Models\{Article, Author};
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;

class CapsuleDebugListenerTest extends TestCase
{
    protected function setUp(): void
    {
        Capsule::connection()->table('authors')->truncate();
        Capsule::connection()->table('articles')->truncate();
    }

    public function test_get_instance(): void
    {
        $instance = CapsuleDebugListener::getInstance();
        $this->assertInstanceOf(CapsuleDebugListener::class, $instance);
        $this->assertSame(CapsuleDebugListener::getInstance(), $instance);
    }

    public function test_get_named_instance(): void
    {
        $instance = CapsuleDebugListener::getInstance('write');
        $this->assertInstanceOf(CapsuleDebugListener::class, $instance);
        $this->assertSame(CapsuleDebugListener::getInstance('write'), $instance);
        $this->assertNotSame(CapsuleDebugListener::getInstance(), $instance);
    }

    public function test_set_connection(): void
    {
        $defaultInstance = CapsuleDebugListener::getInstance()->setConnection(Capsule::connection());
        $this->assertSame('default', $defaultInstance->getConnection()->getName());

        $otherInstance = CapsuleDebugListener::getInstance()->setConnection(Capsule::connection('other'));
        $this->assertSame('other', $otherInstance->getConnection()->getName());
    }

    public function test_dump_queries(): void
    {
        $this->markTestIncomplete('Need to figure out how to capture VarDumper output to a buffer');
    }

    public function test_log_queries(): void
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

        $actualLogs = stop_capturing_queries();

        $this->assertCount(4, $actualLogs);

        $this->assertSame(1, $actualLogs[0]['count']);
        $this->assertSame("insert into \"authors\" (\"name\", \"email\") values ('Arthur Dent', 'arthur@hitchhiker.co.uk')", $actualLogs[0]['sql']);
        $this->assertSame(2, $actualLogs[1]['count']);
        $this->assertSame("insert into \"articles\" (\"title\", \"description\", \"tags\", \"views\", \"author_id\") values ('Always Bring a Towel!', 'When travelling through the galaxy, always bring a towel.', '[\"travel\",\"towel\"]', 42, 1)", $actualLogs[1]['sql']);
        $this->assertSame(3, $actualLogs[2]['count']);
        $this->assertSame('select * from "authors" where "authors"."id" = 1 limit 1', $actualLogs[2]['sql']);
        $this->assertSame(4, $actualLogs[3]['count']);
        $this->assertSame('select * from "articles" where "articles"."id" = 1 limit 1', $actualLogs[3]['sql']);

        $this->assertStringStartsWith('FusionsPim\\Tests\\PhpCapsuleListener\\CapsuleDebugListenerTest:test_log_queries in', $actualLogs[0]['callees'][0]);
        $this->assertStringContainsString('vendor/phpunit/phpunit/src/Framework/TestCase.php:', $actualLogs[0]['callees'][0]);
    }
}
