<?php
/**
 * Mageplaza
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Mageplaza.com license that is
 * available through the world-wide-web at this URL:
 * https://www.mageplaza.com/LICENSE.txt
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade this extension to newer
 * version in the future.
 *
 * @category    Mageplaza
 * @package     Mageplaza_AjaxLayer
 * @copyright   Copyright (c) Mageplaza (https://www.mageplaza.com/)
 * @license     https://www.mageplaza.com/LICENSE.txt
 */

declare(strict_types=1);

namespace Mageplaza\AjaxLayer\Test\Unit;

use Magento\Framework\App\ObjectManager;
use Magento\Framework\Json\Helper\Data as JsonHelper;
use Magento\Framework\ObjectManagerInterface;
use PHPUnit\Framework\TestCase;
use ReflectionProperty;

/**
 * Base test case for code paths that reach the framework ObjectManager singleton
 * through Mageplaza\Core's static AbstractData::jsonEncode(). The singleton is
 * snapshotted before and restored after each test so suites stay isolated and
 * the assertions remain valid across Magento/PHP versions.
 */
abstract class StaticJsonTestCase extends TestCase
{
    /** @var mixed Snapshot of the global ObjectManager instance before the test. */
    private $objectManagerSnapshot;

    private bool $snapshotTaken = false;

    protected function tearDown(): void
    {
        if ($this->snapshotTaken) {
            $this->writeObjectManagerInstance($this->objectManagerSnapshot);
            $this->snapshotTaken = false;
        }
    }

    /**
     * Register a stub global ObjectManager so static jsonEncode() yields real JSON.
     * Services requested through ObjectManagerInterface::create() can be supplied
     * via $extraCreate keyed by class name.
     *
     * @param array<class-string, object> $extraCreate
     */
    protected function registerJsonObjectManager(array $extraCreate = []): void
    {
        $jsonHelper = $this->createMock(JsonHelper::class);
        $jsonHelper->method('jsonEncode')->willReturnCallback(
            static fn ($value) => json_encode($value)
        );

        $objectManager = $this->createMock(ObjectManagerInterface::class);
        $objectManager->method('get')->willReturnCallback(
            static fn ($type) => $type === JsonHelper::class ? $jsonHelper : null
        );
        $objectManager->method('create')->willReturnCallback(
            static fn ($type) => $extraCreate[$type] ?? null
        );

        $this->objectManagerSnapshot = $this->readObjectManagerInstance();
        $this->snapshotTaken = true;
        ObjectManager::setInstance($objectManager);
    }

    /**
     * @return mixed
     */
    private function readObjectManagerInstance()
    {
        $property = new ReflectionProperty(ObjectManager::class, '_instance');

        return $property->getValue();
    }

    /**
     * @param mixed $value
     */
    private function writeObjectManagerInstance($value): void
    {
        $property = new ReflectionProperty(ObjectManager::class, '_instance');
        $property->setValue(null, $value);
    }
}
