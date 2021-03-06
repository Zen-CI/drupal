<?php

namespace Drupal\Tests\Core\Entity;

use Drupal\Core\Config\Entity\ConfigEntityInterface;
use Drupal\Core\DependencyInjection\ContainerBuilder;
use Drupal\Core\Entity\EntityInterface;
use Drupal\Core\Language\Language;
use Drupal\Core\Language\LanguageInterface;
use Drupal\Tests\UnitTestCase;

/**
 * @coversDefaultClass \Drupal\Core\Entity\Entity
 * @group Entity
 */
class EntityUrlTest extends UnitTestCase {

  /**
   * The mocked entity manager.
   *
   * @var \Drupal\Core\Entity\EntityManagerInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $entityManager;

  /**
   * The mocked URL generator.
   *
   * @var \Drupal\Core\Routing\UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
   */
  protected $urlGenerator;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $this->entityManager = $this->getMock('Drupal\Core\Entity\EntityManagerInterface');
    $this->urlGenerator = $this->getMock('Drupal\Core\Routing\UrlGeneratorInterface');

    $container = new ContainerBuilder();
    $container->set('entity.manager', $this->entityManager);
    $container->set('url_generator', $this->urlGenerator);
    \Drupal::setContainer($container);
  }

  /**
   * Tests the urlInfo() method.
   *
   * Note that urlInfo() is a deprecated alias for toUrl().
   * See testToUrl().
   *
   * @covers ::urlInfo
   *
   * @dataProvider providerTestToUrl
   */
  public function testUrlInfo($entity_class, $link_template, $expected, $langcode = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getMockForAbstractClass($entity_class, array(array('id' => 'test_entity_id'), 'test_entity_type'));
    $uri = $this->getTestUrlInfo($entity, $link_template, [], $langcode);

    $this->assertSame($expected, $uri->getRouteName());
    $this->assertSame($entity, $uri->getOption('entity'));

    if ($langcode) {
      $this->assertEquals($langcode, $uri->getOption('language')->getId());
    }
    else {
      if ($entity instanceof ConfigEntityInterface) {
        // Config entities do not provide a language with their URIs.
        $this->assertEquals(NULL, $uri->getOption('language'));
      }
      else {
        $this->assertEquals(LanguageInterface::LANGCODE_NOT_SPECIFIED, $uri->getOption('language')->getId());
      }
    }
  }
  /**
   * Tests the toUrl() method.
   *
   * @covers ::toUrl
   *
   * @dataProvider providerTestToUrl
   */
  public function testToUrl($entity_class, $link_template, $expected, $langcode = NULL) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getMockForAbstractClass($entity_class, array(array('id' => 'test_entity_id'), 'test_entity_type'));
    $uri = $this->getTestToUrl($entity, $link_template, [], $langcode);

    $this->assertSame($expected, $uri->getRouteName());
    $this->assertSame($entity, $uri->getOption('entity'));

    if ($langcode) {
      $this->assertEquals($langcode, $uri->getOption('language')->getId());
    }
    else {
      if ($entity instanceof ConfigEntityInterface) {
        // Config entities do not provide a language with their URIs.
        $this->assertEquals(NULL, $uri->getOption('language'));
      }
      else {
        $this->assertEquals(LanguageInterface::LANGCODE_NOT_SPECIFIED, $uri->getOption('language')->getId());
      }
    }
  }

  /**
   * Tests for Entity::toUrl() exercising different language options.
   *
   * @covers ::toUrl
   */
  public function testToUrlWithSpecificLanguageInOptions() {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'));

    // Ensure that a specified language overrides the current translation
    // language.
    $uri = $this->getTestToUrl($entity, 'edit-form', [], 'en');
    $this->assertEquals('en', $uri->getOption('language')->getId());

    $uri = $this->getTestToUrl($entity, 'edit-form', ['language' => new Language(['id' => 'fr'])], 'en');
    $this->assertEquals('fr', $uri->getOption('language')->getId());
  }

  /**
   * Provides test data for testUrlInfo().
   */
  public function providerTestToUrl() {
    return array(
      array('Drupal\Core\Entity\Entity', 'edit-form', 'entity.test_entity_type.edit_form', NULL),
      // Specify a langcode.
      array('Drupal\Core\Entity\Entity', 'edit-form', 'entity.test_entity_type.edit_form', 'es'),
      array('Drupal\Core\Entity\Entity', 'edit-form', 'entity.test_entity_type.edit_form', 'en'),
      array('Drupal\Core\Config\Entity\ConfigEntityBase', 'edit-form', 'entity.test_entity_type.edit_form', NULL),
      // Test that overriding the default $rel parameter works.
      array('Drupal\Core\Config\Entity\ConfigEntityBase', FALSE, 'entity.test_entity_type.edit_form', NULL),
    );
  }

  /**
   * Tests the toUrl() method with an invalid link template.
   *
   * @covers ::toUrl
   *
   * @expectedException \Drupal\Core\Entity\Exception\UndefinedLinkTemplateException
   *
   * @expectedExceptionMessage No link template 'canonical' found for the 'test_entity_type' entity type
   *
   * @dataProvider providerTestToUrlForInvalidLinkTemplate
   */
  public function testToUrlForInvalidLinkTemplate($entity_class, $link_template) {
    /** @var \Drupal\Core\Entity\EntityInterface $entity */
    $entity = $this->getMockForAbstractClass($entity_class, array(array('id' => 'test_entity_id'), 'test_entity_type'));
    $uri = $this->getTestToUrl($entity, $link_template);

    $this->assertEmpty($uri);
  }

  /**
   * Provides test data for testUrlInfoForInvalidLinkTemplate().
   */
  public function providerTestToUrlForInvalidLinkTemplate() {
    return array(
      array('Drupal\Core\Entity\Entity', 'canonical'),
      array('Drupal\Core\Entity\Entity', FALSE),
      array('Drupal\Core\Config\Entity\ConfigEntityBase', 'canonical'),
    );
  }

  /**
   * Creates a \Drupal\Core\Url object based on the entity and link template.
   *
   * Method urlInfo() is deprecated and replaced with toUrl().
   * See also getTestToUrl().
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The test entity.
   * @param string $link_template
   *   The link template.
   * @param string $langcode
   *   The langcode.
   *
   * @return \Drupal\Core\Url
   *   The URL for this entity's link template.
   */
  protected function getTestUrlInfo(EntityInterface $entity, $link_template, array $options = [], $langcode = NULL) {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'edit-form' => 'test_entity_type.edit',
      )));

    if ($langcode) {
      $entity->langcode = $langcode;
    }

    $this->entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    // If no link template is given, call without a value to test the default.
    if ($link_template) {
      $uri = $entity->urlInfo($link_template, $options);
    }
    else {
      if ($entity instanceof ConfigEntityInterface) {
        $uri = $entity->urlInfo('edit-form', $options);
      }
      else {
        $uri = $entity->urlInfo('canonical', $options);
      }
    }

    return $uri;
  }

  /**
   * Creates a \Drupal\Core\Url object based on the entity and link template.
   *
   * @param \Drupal\Core\Entity\EntityInterface $entity
   *   The test entity.
   * @param string $link_template
   *   The link template.
   * @param string $langcode
   *   The langcode.
   *
   * @return \Drupal\Core\Url
   *   The URL for this entity's link template.
   */
  protected function getTestToUrl(EntityInterface $entity, $link_template, array $options = [], $langcode = NULL) {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'edit-form' => 'test_entity_type.edit',
      )));

    if ($langcode) {
      $entity->langcode = $langcode;
    }

    $this->entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    // If no link template is given, call without a value to test the default.
    if ($link_template) {
      $uri = $entity->toUrl($link_template, $options);
    }
    else {
      if ($entity instanceof ConfigEntityInterface) {
        $uri = $entity->toUrl('edit-form', $options);
      }
      else {
        $uri = $entity->toUrl('canonical', $options);
      }
    }

    return $uri;
  }

  /**
   * Tests the toUrl() method when an entity is still "new".
   *
   * @see \Drupal\Core\Entity\EntityInterface::isNew()
   *
   * @covers ::toUrl
   *
   * @expectedException \Drupal\Core\Entity\EntityMalformedException
   */
  public function testToUrlForNewEntity() {
    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array(), 'test_entity_type'));
    $entity->toUrl();
  }

  /**
   * Tests the url() method.
   *
   * @covers ::url
   */
  public function testUrl() {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'canonical' => 'test_entity_type.view',
      )));

    $this->entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $invalid_entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array(), 'test_entity_type'));
    $this->assertSame('', $invalid_entity->url());

    $no_link_entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'));
    $this->assertSame('', $no_link_entity->url('banana'));

    $valid_entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'));

    $language = new Language(array('id' => LanguageInterface::LANGCODE_NOT_SPECIFIED));
    $this->urlGenerator->expects($this->any())
      ->method('generateFromRoute')
      // Sadly returnValueMap() uses ===, see \PHPUnit_Framework_MockObject_Stub_ReturnValueMap::invoke
      // so the $language object can't be compared directly.
      ->willReturnCallback(function ($route_name, $route_parameters, $options) use ($language) {
        if ($route_name === 'entity.test_entity_type.canonical' && $route_parameters === array('test_entity_type' => 'test_entity_id') && array_keys($options) === ['entity_type', 'entity', 'language'] && $options['language'] == $language) {
          return '/entity/test_entity_type/test_entity_id';
        }
        if ($route_name === 'entity.test_entity_type.canonical' && $route_parameters === array('test_entity_type' => 'test_entity_id') && array_keys($options) === ['absolute', 'entity_type', 'entity', 'language'] && $options['language'] == $language) {
          return 'http://drupal/entity/test_entity_type/test_entity_id';
        }
      });

    $this->assertSame('/entity/test_entity_type/test_entity_id', $valid_entity->url());
    $this->assertSame('http://drupal/entity/test_entity_type/test_entity_id', $valid_entity->url('canonical', array('absolute' => TRUE)));
  }

  /**
   * Tests the retrieval of link templates.
   *
   * @covers ::hasLinkTemplate
   * @covers ::linkTemplates
   *
   * @dataProvider providerTestLinkTemplates
   */
  public function testLinkTemplates($override_templates, $expected) {
    $entity_type = $this->getMock('Drupal\Core\Entity\EntityTypeInterface');
    $entity_type->expects($this->any())
      ->method('getLinkTemplates')
      ->will($this->returnValue(array(
        'canonical' => 'test_entity_type.view',
      )));

    $this->entityManager
      ->expects($this->any())
      ->method('getDefinition')
      ->with('test_entity_type')
      ->will($this->returnValue($entity_type));

    $entity = $this->getMockForAbstractClass('Drupal\Core\Entity\Entity', array(array('id' => 'test_entity_id'), 'test_entity_type'), '', TRUE, TRUE, TRUE, array('linkTemplates'));
    $entity->expects($this->any())
      ->method('linkTemplates')
      ->will($this->returnCallback(function () use ($entity_type, $override_templates) {
        $templates = $entity_type->getLinkTemplates();
        if ($override_templates) {
          $templates['bananas'] = 'test_entity_type.bananas';
        }
        return $templates;
      }));
    $this->assertSame($expected['canonical'], $entity->hasLinkTemplate('canonical'));
    $this->assertSame($expected['bananas'], $entity->hasLinkTemplate('bananas'));
  }

  /**
   * Provides test data for testLinkTemplates().
   */
  public function providerTestLinkTemplates() {
    return array(
      array(FALSE, array(
        'canonical' => TRUE,
        'bananas' => FALSE,
      )),
      array(TRUE, array(
        'canonical' => TRUE,
        'bananas' => TRUE,
      )),
    );
  }

}
