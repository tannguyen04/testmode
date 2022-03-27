<?php

namespace Drupal\Tests\testmode\Functional;

use Drupal\views\Views;

/**
 * Tests the node views.
 *
 * @group Testmode
 */
class NodeViewsTest extends TestmodeTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'views'];

  /**
   * Views used by this test.
   *
   * @var array
   */
  public static $testViews = ['test_testmode_node'];

  /**
   * Test node view without caching.
   */
  public function testNodeViewNoCache() {
    $this->createNodes();

    // Login to bypass page caching.
    $this->drupalLogin($this->drupalCreateUser());

    // Add test view to a list of views.
    $this->testmode->setNodeViews('test_testmode_node');

    $this->drupalGet('/test-testmode-node');
    $this->drupalGet('/test-testmode-node');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->assertText('Article 1');
    $this->assertText('Article 2');
    $this->assertText('[TEST] Article 3');

    $this->drupalGet('/test-testmode-node');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->testmode->enableTestMode();

    $this->drupalGet('/test-testmode-node');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->assertNoText('Article 1');
    $this->assertNoText('Article 2');
    $this->assertText('[TEST] Article 3');

    $this->drupalGet('/test-testmode-node');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');
  }

  /**
   * Test node view with tag-based caching.
   */
  public function testNodeViewCacheTag() {
    $this->createNodes();

    // Login to bypass page caching.
    $this->drupalLogin($this->drupalCreateUser());

    // Add test view to a list of Testmode views.
    $this->testmode->setNodeViews('test_testmode_node');

    // Enable Tag caching for this view.
    $view = Views::getView('test_testmode_node');
    $view->setDisplay('page_1');
    $view->display_handler->overrideOption('cache', [
      'type' => 'tag',
    ]);
    $view->save();

    $this->drupalGet('/test-testmode-node');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'MISS');

    $this->assertText('Article 1');
    $this->assertText('Article 2');
    $this->assertText('[TEST] Article 3');

    $this->drupalGet('/test-testmode-node');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'HIT');

    $this->testmode->enableTestMode();

    $this->drupalGet('/test-testmode-node');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'MISS');

    $this->assertNoText('Article 1');
    $this->assertNoText('Article 2');
    $this->assertText('[TEST] Article 3');

    $this->drupalGet('/test-testmode-node');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'HIT');
  }

  /**
   * Test node view for default Content with tag-based caching.
   */
  public function testNodeViewContentNoCache() {
    $this->createNodes();

    // Disable Tag caching for this view.
    $view = Views::getView('content');
    $view->setDisplay('page_1');
    $view->display_handler->overrideOption('cache', [
      'type' => 'none',
    ]);
    $view->save();

    // Login to bypass page caching.
    $this->drupalLoginAdmin();

    $this->drupalGet('/admin/content');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->assertText('Article 1');
    $this->assertText('Article 2');
    $this->assertText('[TEST] Article 3');

    $this->drupalGet('/admin/content');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->testmode->enableTestMode();

    $this->drupalGet('/admin/content');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');

    $this->assertNoText('Article 1');
    $this->assertNoText('Article 2');
    $this->assertText('[TEST] Article 3');

    $this->drupalGet('/admin/content');
    $this->assertHeader('X-Drupal-Dynamic-Cache', 'UNCACHEABLE');
  }

  /**
   * Helper to create nodes.
   */
  protected function createNodes() {
    for ($i = 0; $i < 2; $i++) {
      $this->drupalCreateNode([
        'type' => 'article',
        'title' => sprintf('Article %s %s', $i + 1, $this->randomMachineName()),
      ]);
    }

    $this->drupalCreateNode([
      'type' => 'article',
      'title' => sprintf('[TEST] Article %s %s', 3, $this->randomMachineName()),
    ]);
  }

}