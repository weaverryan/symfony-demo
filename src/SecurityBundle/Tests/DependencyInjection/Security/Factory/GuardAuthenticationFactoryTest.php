<?php

/*
 * This file is part of the Symfony package.
 *
 * (c) Fabien Potencier <fabien@symfony.com>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

namespace Symfony\Bundle\SecurityBundle\Tests\DependencyInjection\Security\Factory;

use Symfony\Bundle\SecurityBundle\DependencyInjection\Security\Factory\GuardAuthenticationFactory;
use Symfony\Component\Config\Definition\Builder\ArrayNodeDefinition;

class GuardAuthenticationFactoryTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @dataProvider getValidConfigurationTests
     */
    public function testAddValidConfiguration(array $inputConfig, array $expectedConfig)
    {
        $factory = new GuardAuthenticationFactory();
        $nodeDefinition = new ArrayNodeDefinition('guard');
        $factory->addConfiguration($nodeDefinition);

        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($inputConfig);
        $finalizedConfig = $node->finalize($normalizedConfig);

        $this->assertEquals($expectedConfig, $finalizedConfig);
    }

    /**
     * @expectedException \Symfony\Component\Config\Definition\Exception\InvalidConfigurationException
     * @dataProvider getInvalidConfigurationTests
     */
    public function testAddInvalidConfiguration(array $inputConfig)
    {
        $factory = new GuardAuthenticationFactory();
        $nodeDefinition = new ArrayNodeDefinition('guard');
        $factory->addConfiguration($nodeDefinition);

        $node = $nodeDefinition->getNode();
        $normalizedConfig = $node->normalize($inputConfig);
        $finalizedConfig = $node->finalize($normalizedConfig);
    }

    public function getValidConfigurationTests()
    {
        $tests = array();

        // completely basic
        $tests[] = array(
            array(
                'authenticators' => array('authenticator1', 'authenticator2'),
                'provider' => 'some_provider',
                'entry_point' => 'the_entry_point'
            ),
            array(
                'authenticators' => array('authenticator1', 'authenticator2'),
                'provider' => 'some_provider',
                'entry_point' => 'the_entry_point'
            )
        );

        // testing xml config fix: authenticator -> authenticators
        $tests[] = array(
            array(
                'authenticator' => array('authenticator1', 'authenticator2'),
            ),
            array(
                'authenticators' => array('authenticator1', 'authenticator2'),
                'entry_point' => null,
            )
        );

        return $tests;
    }

    public function getInvalidConfigurationTests()
    {
        $tests = array();

        // testing not empty
        $tests[] = array(
            array('authenticators' => array())
        );

        return $tests;
    }
}
