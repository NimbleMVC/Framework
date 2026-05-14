<?php

use NimblePHP\Framework\DataStore;
use PHPUnit\Framework\TestCase;

class DataStoreTest extends TestCase
{
    public function testSetGetAndExists(): void
    {
        $store = new DataStore();
        $store->set('name', 'Nimble');

        $this->assertTrue($store->exists('name'));
        $this->assertSame('Nimble', $store->get('name'));
        $this->assertSame('default', $store->get('missing', 'default'));
    }

    public function testAppendSupportsArrayStringAndIntegerValues(): void
    {
        $store = new DataStore();
        $store->set('items', ['first']);
        $store->set('text', 'Hello');
        $store->set('count', 2);

        $this->assertTrue($store->append('items', 'second'));
        $this->assertTrue($store->append('items', 'named', 'custom'));
        $this->assertTrue($store->append('text', ' World'));
        $this->assertTrue($store->append('count', 3));

        $this->assertSame(['first', 'second', 'custom' => 'named'], $store->get('items'));
        $this->assertSame('Hello World', $store->get('text'));
        $this->assertSame(5, $store->get('count'));
    }

    public function testAppendReturnsFalseForMissingKey(): void
    {
        $store = new DataStore();

        $this->assertFalse($store->append('missing', 'value'));
    }

    public function testPullReturnsValueAndRemovesKey(): void
    {
        $store = new DataStore();
        $store->set('flash', 'saved');

        $this->assertSame('saved', $store->pull('flash'));
        $this->assertFalse($store->exists('flash'));
        $this->assertSame('fallback', $store->pull('flash', 'fallback'));
    }

    public function testRemoveAndClear(): void
    {
        $store = new DataStore();
        $store->set('a', 1);
        $store->set('b', 2);
        $store->remove('a');

        $this->assertFalse($store->exists('a'));
        $this->assertTrue($store->exists('b'));

        $store->clear();

        $this->assertTrue($store->isEmpty());
    }

    public function testGetRequiredThrowsForMissingKey(): void
    {
        $store = new DataStore();

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing data key: missing');
        $store->getRequired('missing');
    }

    public function testMergeAndReplaceAcceptArraysAndStores(): void
    {
        $store = new DataStore();
        $store->set('base', 1);
        $store->merge(['a' => 2]);

        $other = new DataStore();
        $other->set('b', 3);
        $store->merge($other);

        $this->assertSame(['base' => 1, 'a' => 2, 'b' => 3], $store->all());

        $replacement = new DataStore();
        $replacement->set('final', 'value');
        $store->replace($replacement);

        $this->assertSame(['final' => 'value'], $store->all());

        $store->replace(['x' => 10, 'y' => 20]);

        $this->assertSame(['x', 'y'], $store->keys());
        $this->assertSame([10, 20], $store->values());
        $this->assertSame(2, $store->count());
    }
}
