<?php
/**
 * AbstractUserGroupTest.php
 *
 * The AbstractUserGroupTest unit test class file.
 *
 * PHP versions 5
 *
 * @author    Alexander Schneider <alexanderschneider85@gmail.com>
 * @copyright 2008-2017 Alexander Schneider
 * @license   http://www.gnu.org/licenses/gpl-2.0.html  GNU General Public License, version 2
 * @version   SVN: $id$
 * @link      http://wordpress.org/extend/plugins/user-access-manager/
 */
namespace UserAccessManager\UserGroup;

use PHPUnit_Extensions_Constraint_StringMatchIgnoreWhitespace as MatchIgnoreWhitespace;
use UserAccessManager\Config\MainConfig;
use UserAccessManager\Database\Database;
use UserAccessManager\ObjectHandler\ObjectHandler;
use UserAccessManager\UserAccessManagerTestCase;
use UserAccessManager\Util\Util;
use UserAccessManager\Wrapper\Php;
use UserAccessManager\Wrapper\Wordpress;

/**
 * Class AbstractUserGroupTest
 *
 * @package UserAccessManager\UserGroup
 */
class AbstractUserGroupTest extends UserAccessManagerTestCase
{
    /**
     * @param Php           $php
     * @param Wordpress     $wordpress
     * @param Database      $database
     * @param MainConfig    $config
     * @param Util          $util
     * @param ObjectHandler $objectHandler
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|AbstractUserGroup
     */
    private function getStub(
        Php $php,
        Wordpress $wordpress,
        Database $database,
        MainConfig $config,
        Util $util,
        ObjectHandler $objectHandler
    ) {
        return $this->getMockForAbstractClass(
            '\UserAccessManager\UserGroup\AbstractUserGroup',
            [
                $php,
                $wordpress,
                $database,
                $config,
                $util,
                $objectHandler
            ]
        );
    }
    
    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::__construct()
     */
    public function testCanCreateInstance()
    {
        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::assertInstanceOf('\UserAccessManager\UserGroup\AbstractUserGroup', $abstractUserGroup);
    }

    /**
     * @group   unit
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::getId()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::getName()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::getDescription()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::getReadAccess()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::getWriteAccess()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::getIpRange()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::getIpRangeArray()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::setName()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::setDescription()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::setReadAccess()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::setWriteAccess()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::setIpRange()
     */
    public function testSimpleGetterSetter()
    {
        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $this->getDatabase(),
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::setValue($abstractUserGroup, 'id', 2);
        self::setValue($abstractUserGroup, 'type', 'type');
        self::setValue($abstractUserGroup, 'name', 'groupName');
        self::setValue($abstractUserGroup, 'description', 'groupDesc');
        self::setValue($abstractUserGroup, 'readAccess', 'readAccess');
        self::setValue($abstractUserGroup, 'writeAccess', 'writeAccess');
        self::setValue($abstractUserGroup, 'ipRange', 'ipRange;ipRange2');

        self::assertEquals(2, $abstractUserGroup->getId());
        self::assertEquals('type', $abstractUserGroup->getType());
        self::assertEquals('groupName', $abstractUserGroup->getName());
        self::assertEquals('groupDesc', $abstractUserGroup->getDescription());
        self::assertEquals('readAccess', $abstractUserGroup->getReadAccess());
        self::assertEquals('writeAccess', $abstractUserGroup->getWriteAccess());
        self::assertEquals(['ipRange', 'ipRange2'], $abstractUserGroup->getIpRangeArray());
        self::assertEquals('ipRange;ipRange2', $abstractUserGroup->getIpRange());

        $abstractUserGroup->setName('groupNameNew');
        self::assertAttributeEquals('groupNameNew', 'name', $abstractUserGroup);

        $abstractUserGroup->setDescription('groupDescNew');
        self::assertAttributeEquals('groupDescNew', 'description', $abstractUserGroup);

        $abstractUserGroup->setReadAccess('readAccessNew');
        self::assertAttributeEquals('readAccessNew', 'readAccess', $abstractUserGroup);

        $abstractUserGroup->setWriteAccess('writeAccessNew');
        self::assertAttributeEquals('writeAccessNew', 'writeAccess', $abstractUserGroup);

        $abstractUserGroup->setIpRange(['ipRangeNew', 'ipRangeNew2']);
        self::assertAttributeEquals('ipRangeNew;ipRangeNew2', 'ipRange', $abstractUserGroup);
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::addObject()
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::resetObjects()
     */
    public function testAddObject()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(2))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(2))
            ->method('insert')
            ->with(
                'userGroupToObjectTable',
                [
                    'group_id' => 123,
                    'group_type' => 'type',
                    'object_id' => 321,
                    'general_object_type' => 'generalObjectType',
                    'object_type' => 'objectType'
                ],
                ['%d', '%s', '%s', '%s']
            )
            ->will($this->onConsecutiveCalls(false, true));

        $objectHandler = $this->getObjectHandler();

        $objectHandler->expects($this->exactly(5))
            ->method('getGeneralObjectType')
            ->withConsecutive(
                ['invalid'],
                ['generalObjectType'],
                ['notValidObjectType'],
                ['objectType'],
                ['objectType']
            )
            ->will($this->onConsecutiveCalls(
                null,
                null,
                'generalNotValidObjectType',
                'generalObjectType',
                'generalObjectType'
            ));

        $objectHandler->expects($this->exactly(3))
            ->method('isValidObjectType')
            ->withConsecutive(['notValidObjectType'], ['objectType'], ['objectType'])
            ->will($this->onConsecutiveCalls(false, true, true));

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($abstractUserGroup, 'id', 123);
        self::setValue($abstractUserGroup, 'type', 'type');
        self::setValue($abstractUserGroup, 'assignedObjects', [1 => 'post', 2 => 'post']);
        self::setValue($abstractUserGroup, 'roleMembership', [1 => 'role', 2 => 'role']);
        self::setValue($abstractUserGroup, 'userMembership', [1 => 'user', 2 => 'user']);
        self::setValue($abstractUserGroup, 'termMembership', [1 => 'term', 2 => 'term']);
        self::setValue($abstractUserGroup, 'postMembership', [1 => 'post', 2 => 'post']);
        self::setValue($abstractUserGroup, 'fullObjectMembership', [1 => 'post', 2 => 'post']);

        self::assertFalse($abstractUserGroup->addObject('invalid', 321));
        self::assertFalse($abstractUserGroup->addObject('generalObjectType', 321));
        self::assertFalse($abstractUserGroup->addObject('notValidObjectType', 321));
        self::assertFalse($abstractUserGroup->addObject('objectType', 321));
        self::assertTrue($abstractUserGroup->addObject('objectType', 321));

        self::assertAttributeEquals([], 'assignedObjects', $abstractUserGroup);
        self::assertAttributeEquals([], 'roleMembership', $abstractUserGroup);
        self::assertAttributeEquals([], 'userMembership', $abstractUserGroup);
        self::assertAttributeEquals([], 'termMembership', $abstractUserGroup);
        self::assertAttributeEquals([], 'postMembership', $abstractUserGroup);
        self::assertAttributeEquals([], 'fullObjectMembership', $abstractUserGroup);
    }

    /**
     * Generates return values.
     *
     * @param int    $number
     * @param string $type
     *
     * @return array
     */
    private function generateReturn($number, $type)
    {
        $returns = [];

        for ($counter = 1; $counter <= $number; $counter++) {
            $return = new \stdClass();
            $return->id = $counter;
            $return->objectType = $type;
            $returns[] = $return;
        }

        return $returns;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::getAssignedObjects()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::isObjectAssignedToGroup()
     */
    public function testAssignedObject()
    {
        $database = $this->getDatabase();

        $database->expects($this->exactly(3))
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $query = 'SELECT object_id AS id, object_type AS objectType
            FROM userGroupToObjectTable
            WHERE group_id = %d
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $database->expects($this->exactly(3))
            ->method('prepare')
            ->withConsecutive(
                [new MatchIgnoreWhitespace($query), [123, 'noResultObjectType', 'noResultObjectType']],
                [new MatchIgnoreWhitespace($query), [123, 'objectType', 'objectType']],
                [new MatchIgnoreWhitespace($query), [123, 'something', 'something']]
            )
            ->will($this->onConsecutiveCalls(
                'nonResultPreparedQuery',
                'preparedQuery',
                'nonResultSomethingPreparedQuery'
            ));

        $database->expects($this->exactly(3))
            ->method('getResults')
            ->withConsecutive(
                ['nonResultPreparedQuery'],
                ['preparedQuery'],
                ['nonResultSomethingPreparedQuery']
            )
            ->will($this->onConsecutiveCalls(null, $this->generateReturn(3, 'objectType'), null));

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::setValue($abstractUserGroup, 'id', 123);

        $result = self::callMethod($abstractUserGroup, 'getAssignedObjects', ['noResultObjectType']);
        self::assertEquals([], $result);
        self::assertAttributeEquals(['noResultObjectType' => []], 'assignedObjects', $abstractUserGroup);

        $result = self::callMethod($abstractUserGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals([1 => 'objectType', 2 => 'objectType', 3 => 'objectType'], $result);
        self::assertAttributeEquals(
            ['noResultObjectType' => [], 'objectType' => [1 => 'objectType', 2 => 'objectType', 3 => 'objectType']],
            'assignedObjects',
            $abstractUserGroup
        );

        $result = self::callMethod($abstractUserGroup, 'getAssignedObjects', ['objectType']);
        self::assertEquals([1 => 'objectType', 2 => 'objectType', 3 => 'objectType'], $result);

        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['objectType', 1]);
        self::assertTrue($result);
        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['objectType', 2]);
        self::assertTrue($result);
        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['objectType', 3]);
        self::assertTrue($result);

        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['objectType', 4]);
        self::assertFalse($result);
        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['noResultObjectType', 1]);
        self::assertFalse($result);
        $result = self::callMethod($abstractUserGroup, 'isObjectAssignedToGroup', ['something', 1]);
        self::assertFalse($result);
    }

    /**
     * Returns the database mock for the member tests
     *
     * @param array $types
     * @param array $getResultsWith
     * @param array $getResultsWill
     *
     * @return \PHPUnit_Framework_MockObject_MockObject|\UserAccessManager\Database\Database
     */
    private function getDatabaseMockForMemberTests(
        array $types,
        array $getResultsWith = [],
        array $getResultsWill = []
    ) {
        $query = 'SELECT object_id AS id, object_type AS objectType
            FROM userGroupToObjectTable
            WHERE group_id = %d
              AND (general_object_type = \'%s\' OR object_type = \'%s\')';

        $prepareWith = [];
        $prepareWill = [];

        foreach ($types as $type => $numberOfReturn) {
            $prepareWith[] = [new MatchIgnoreWhitespace($query), [123, "_{$type}_", "_{$type}_"]];
            $prepareWill[] = "{$type}PreparedQuery";
            $getResultsWith[] = ["{$type}PreparedQuery"];
            $getResultsWill[] = $this->generateReturn($numberOfReturn, $type);
        }

        $database = $this->getDatabase();

        $database->expects($this->any())
            ->method('getUserGroupToObjectTable')
            ->will($this->returnValue('userGroupToObjectTable'));

        $database->expects($this->exactly(count($prepareWith)))
            ->method('prepare')
            ->withConsecutive(...$prepareWith)
            ->will($this->onConsecutiveCalls(...$prepareWill));

        $database->expects($this->exactly(count($getResultsWith)))
            ->method('getResults')
            ->withConsecutive(...$getResultsWith)
            ->will($this->onConsecutiveCalls(...$getResultsWill));

        return $database;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isRoleMember()
     *
     * @return AbstractUserGroup
     */
    public function testIsRoleMember()
    {
        $database = $this->getDatabaseMockForMemberTests(['role' => 3]);

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $this->getObjectHandler()
        );

        self::setValue($abstractUserGroup, 'id', 123);
        $recursiveMembership = [];

        $return = $abstractUserGroup->isRoleMember(1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $abstractUserGroup->isRoleMember(4, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        return $abstractUserGroup;
    }

    /**
     * Prototype function for the testIsUserMember
     *
     * @param array $types
     * @param array $getResultsWith
     * @param array $getResultsWill
     * @param array $arrayFillWith
     * @param int   $expectGetUsersTable
     * @param int   $expectGetCapabilitiesTable
     * @param int   $expectGetUser
     *
     * @return AbstractUserGroup
     */
    private function getTestIsUserMemberPrototype(
        array $types,
        array $getResultsWith,
        array $getResultsWill,
        array $arrayFillWith,
        $expectGetUsersTable,
        $expectGetCapabilitiesTable,
        $expectGetUser
    ) {
        $php = $this->getPhp();

        $php->expects($this->exactly(count($arrayFillWith)))
            ->method('arrayFill')
            ->withConsecutive(...$arrayFillWith)
            ->will($this->returnCallback(function ($startIndex, $numberOfElements, $value) {
                return array_fill($startIndex, $numberOfElements, $value);
            }));

        $database = $this->getDatabaseMockForMemberTests(
            $types,
            $getResultsWith,
            $getResultsWill
        );

        $database->expects($this->exactly($expectGetUsersTable))
            ->method('getUsersTable')
            ->will($this->returnValue('usersTable'));

        $database->expects($this->exactly($expectGetCapabilitiesTable))
            ->method('getCapabilitiesTable')
            ->will($this->returnValue('capabilitiesTable'));

        /**
         * @var \stdClass $firstUser
         */
        $firstUser = $this->getMockBuilder('\WP_User')->getMock();
        $firstUser->capabilitiesTable = [1 => 1, 2 => 2];

        /**
         * @var \stdClass $secondUser
         */
        $secondUser = $this->getMockBuilder('\WP_User')->getMock();
        $secondUser->capabilitiesTable = 'invalid';

        /**
         * @var \stdClass $thirdUser
         */
        $thirdUser = $this->getMockBuilder('\WP_User')->getMock();
        $thirdUser->capabilitiesTable = [1 => 1];

        /**
         * @var \stdClass $fourthUser
         */
        $fourthUser = $this->getMockBuilder('\WP_User')->getMock();
        $fourthUser->capabilitiesTable = [];

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly($expectGetUser))
            ->method('getUser')
            ->will($this->returnCallback(
                function ($userId) use (
                    $firstUser,
                    $secondUser,
                    $thirdUser,
                    $fourthUser
                ) {
                    if ($userId === 1) {
                        return $firstUser;
                    } elseif ($userId === 2) {
                        return $secondUser;
                    } elseif ($userId === 3) {
                        return $thirdUser;
                    } elseif ($userId === 4) {
                        return $fourthUser;
                    }

                    return false;
                }
            ));

        $abstractUserGroup = $this->getStub(
            $php,
            $this->getWordpress(),
            $database,
            $this->getMainConfig(),
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($abstractUserGroup, 'id', 123);

        return $abstractUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isUserMember()
     *
     * @return AbstractUserGroup
     */
    public function testIsUserMember()
    {
        $abstractUserGroup = $this->getTestIsUserMemberPrototype(
            ['role' => 3, 'user' => 2],
            [],
            [],
            [
                [0, 2, ObjectHandler::GENERAL_ROLE_OBJECT_TYPE],
                [0, 1, ObjectHandler::GENERAL_ROLE_OBJECT_TYPE]
            ],
            0,
            5,
            6
        );
        $recursiveMembership = [];

        self::setValue($abstractUserGroup, 'assignedObjects', [ObjectHandler::GENERAL_USER_OBJECT_TYPE => []]);
        $return = $abstractUserGroup->isUserMember(4, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);
        self::setValue($abstractUserGroup, 'assignedObjects', [
            ObjectHandler::GENERAL_USER_OBJECT_TYPE => [],
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => []
        ]);
        $return = $abstractUserGroup->isUserMember(3, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);
        self::setValue($abstractUserGroup, 'userMembership', []);
        self::setValue($abstractUserGroup, 'assignedObjects', []);

        $return = $abstractUserGroup->isUserMember(1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                    1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                    2 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                ]
            ],
            $recursiveMembership
        );

        $return = $abstractUserGroup->isUserMember(2, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $abstractUserGroup->isUserMember(3, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([
            ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
            ]
        ], $recursiveMembership);

        $return = $abstractUserGroup->isUserMember(5, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        return $abstractUserGroup;
    }

    /**
     * Prototype function for the testIsTermMember
     *
     * @return AbstractUserGroup
     */
    private function getTestIsTermMemberPrototype()
    {
        $database = $this->getDatabaseMockForMemberTests(['term' => 3]);

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->exactly(4))
            ->method('getTermTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        1 => [3 => 'term'],
                        2 => [3 => 'term'],
                        4 => [1 => 'term']
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        3 => [1 => 'term', 2 => 'term'],
                        1 => [4 => 'term']
                    ]
                ]
            ]));

        $objectHandler->expects($this->any())
            ->method('isTaxonomy')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === 'termObjectType');
            }));

        $config = $this->getMainConfig();
        $config->expects($this->exactly(5))
            ->method('lockRecursive')
            ->will($this->onConsecutiveCalls(false, true, true, true, true));

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $config,
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($abstractUserGroup, 'id', 123);

        return $abstractUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isTermMember()
     *
     * @return AbstractUserGroup
     */
    public function testIsTermMember()
    {
        $abstractUserGroup = $this->getTestIsTermMemberPrototype();
        $recursiveMembership = [];

        // term tests
        $return = $abstractUserGroup->isTermMember(1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $abstractUserGroup->isTermMember(2, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']],
            $recursiveMembership
        );

        $return = $abstractUserGroup->isTermMember(3, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $abstractUserGroup->isTermMember(4, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => 'term']],
            $recursiveMembership
        );

        $return = $abstractUserGroup->isTermMember(5, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        return $abstractUserGroup;
    }

    /**
     * Prototype function for the testIsPostMember
     *
     * @return AbstractUserGroup
     */
    private function getTestIsPostMemberPrototype()
    {
        $database = $this->getDatabaseMockForMemberTests(['post' => 3, 'term' => 3]);
        $config = $this->getMainConfig();

        $lockRecursiveReturns = [false, true, true, true, true, false];

        $config->expects($this->any())
            ->method('lockRecursive')
            ->will($this->returnCallback(function () use (&$lockRecursiveReturns) {
                if (count($lockRecursiveReturns) > 0) {
                    return array_shift($lockRecursiveReturns);
                }

                return true;
            }));

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getTermTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        1 => [3 => 'term'],
                        2 => [3 => 'term'],
                        4 => [1 => 'term']
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [
                        3 => [1 => 'term', 2 => 'term'],
                        1 => [4 => 'term']
                    ]
                ]
            ]));

        $objectHandler->expects($this->any())
            ->method('isPostType')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === 'postObjectType');
            }));

        $objectHandler->expects($this->any())
            ->method('getPostTreeMap')
            ->will($this->returnValue([
                ObjectHandler::TREE_MAP_PARENTS => [
                    ObjectHandler::GENERAL_POST_OBJECT_TYPE => [
                        1 => [3 => 'post'],
                        2 => [3 => 'post'],
                        4 => [1 => 'post']
                    ]
                ],
                ObjectHandler::TREE_MAP_CHILDREN => [
                    ObjectHandler::GENERAL_POST_OBJECT_TYPE => [
                        3 => [1 => 'post', 2 => 'post'],
                        1 => [4 => 'post']
                    ]
                ]
            ]));

        $objectHandler->expects($this->any())
            ->method('getPostTermMap')
            ->will($this->returnValue([
                2 => [3 => 'term', 9 => 'term'],
                10 => [3 => 'term']
            ]));

        $objectHandler->expects($this->any())
            ->method('getTermPostMap')
            ->will($this->returnValue([
                2 => [9 => 'post']
            ]));

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $config,
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($abstractUserGroup, 'id', 123);

        return $abstractUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isPostMember()
     *
     * @return AbstractUserGroup
     */
    public function testIsPostMember()
    {
        $abstractUserGroup = $this->getTestIsPostMemberPrototype();
        $recursiveMembership = [];

        // post tests
        $return = $abstractUserGroup->isPostMember(1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $abstractUserGroup->isPostMember(2, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 'post'],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']
            ],
            $recursiveMembership
        );

        $return = $abstractUserGroup->isPostMember(3, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        $return = $abstractUserGroup->isPostMember(4, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([ObjectHandler::GENERAL_POST_OBJECT_TYPE => [1 => 'post']], $recursiveMembership);

        $return = $abstractUserGroup->isPostMember(5, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        $return = $abstractUserGroup->isPostMember(10, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']],
            $recursiveMembership
        );

        return $abstractUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isObjectRecursiveMember()
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::isPluggableObjectMember()
     *
     * @return AbstractUserGroup
     */
    public function testIsPluggableObjectMember()
    {
        $database = $this->getDatabaseMockForMemberTests(['pluggableObject' => 2]);

        $objectHandler = $this->getObjectHandler();
        $objectHandler->expects($this->any())
            ->method('getPluggableObject')
            ->will($this->returnCallback(
                function ($objectType) {
                    if ($objectType === '_pluggableObject_') {
                        $pluggableObject = $this->getMockForAbstractClass(
                            '\UserAccessManager\ObjectHandler\PluggableObject',
                            [],
                            '',
                            false
                        );

                        $pluggableObject->expects($this->any())
                            ->method('getRecursiveMembership')
                            ->will($this->returnCallback(
                                function ($abstractUserGroup, $objectId) {
                                    return ($objectId === 1 || $objectId === 4) ?
                                        ['pluggableObject' => [1 => 'pluggableObject']] : [];
                                }
                            ));

                        $pluggableObject->expects($this->any())
                            ->method('getFullObjects')
                            ->will($this->returnValue([1 => 'pluggableObject', 6 => 'pluggableObject']));

                        return $pluggableObject;
                    }

                    return null;
                }
            ));

        $objectHandler->expects($this->any())
            ->method('isPluggableObject')
            ->will($this->returnCallback(function ($objectType) {
                return ($objectType === '_pluggableObject_');
            }));

        $abstractUserGroup = $this->getStub(
            $this->getPhp(),
            $this->getWordpress(),
            $database,
            $config = $this->getMainConfig(),
            $this->getUtil(),
            $objectHandler
        );

        self::setValue($abstractUserGroup, 'id', 123);
        $recursiveMembership = [];

        // pluggable object tests
        $return = $abstractUserGroup->isPluggableObjectMember('noPluggableObject', 1, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        $return = $abstractUserGroup->isPluggableObjectMember('_pluggableObject_', 1, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(['pluggableObject' => [1 => 'pluggableObject']], $recursiveMembership);

        $return = $abstractUserGroup->isPluggableObjectMember('_pluggableObject_', 2, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals([], $recursiveMembership);

        self::assertAttributeEquals(
            [
                'noPluggableObject' => [1 => false],
                '_pluggableObject_' => [
                    1 => ['pluggableObject' => [1 => 'pluggableObject']],
                    2 => []
                ]
            ],
            'pluggableObjectMembership',
            $abstractUserGroup
        );

        $return = $abstractUserGroup->isPluggableObjectMember('_pluggableObject_', 3, $recursiveMembership);
        self::assertFalse($return);
        self::assertEquals([], $recursiveMembership);

        $return = $abstractUserGroup->isPluggableObjectMember('_pluggableObject_', 4, $recursiveMembership);
        self::assertTrue($return);
        self::assertEquals(['pluggableObject' => [1 => 'pluggableObject']], $recursiveMembership);

        return $abstractUserGroup;
    }

    /**
     * Assertion helper for testIsMemberFunctions
     *
     * @param AbstractUserGroup $abstractUserGroup
     * @param bool              $expectedReturn
     * @param array             $expectedRecursiveMembership
     * @param string            $objectType
     * @param string            $objectId
     */
    private function memberFunctionAssertions(
        AbstractUserGroup $abstractUserGroup,
        $expectedReturn,
        array $expectedRecursiveMembership,
        $objectType,
        $objectId
    ) {
        $recursiveMembership = [];
        $return = $abstractUserGroup->isObjectMember($objectType, $objectId, $recursiveMembership);

        self::assertEquals($expectedReturn, $return);
        self::assertEquals($expectedRecursiveMembership, $recursiveMembership);

        self::assertEquals(
            $expectedRecursiveMembership,
            $abstractUserGroup->getRecursiveMembershipForObject(
                $objectType,
                $objectId
            )
        );

        self::assertEquals(
            count($expectedRecursiveMembership) > 0,
            $abstractUserGroup->isLockedRecursive($objectType, $objectId)
        );
    }

    /**
     * @group   unit
     * @depends testIsRoleMember
     * @depends testIsUserMember
     * @depends testIsTermMember
     * @depends testIsPostMember
     * @depends testIsPluggableObjectMember
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::isObjectMember()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::getRecursiveMembershipForObject()
     * @covers  \UserAccessManager\UserGroup\AbstractUserGroup::isLockedRecursive()
     *
     * @param AbstractUserGroup $roleUserGroup
     * @param AbstractUserGroup $userUserGroup
     * @param AbstractUserGroup $termUserGroup
     * @param AbstractUserGroup $postUserGroup
     * @param AbstractUserGroup $pluggableObjectUserGroup
     */
    public function testIsMemberFunctions(
        AbstractUserGroup $roleUserGroup,
        AbstractUserGroup $userUserGroup,
        AbstractUserGroup $termUserGroup,
        AbstractUserGroup $postUserGroup,
        AbstractUserGroup $pluggableObjectUserGroup
    ) {
        // role tests
        $this->memberFunctionAssertions($roleUserGroup, true, [], ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions($roleUserGroup, false, [], ObjectHandler::GENERAL_ROLE_OBJECT_TYPE, 4);

        // user tests
        $this->memberFunctionAssertions(
            $userUserGroup,
            true,
            [
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                    1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE,
                    2 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                ]
            ],
            ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            1
        );
        $this->memberFunctionAssertions($userUserGroup, true, [], ObjectHandler::GENERAL_USER_OBJECT_TYPE, 2);
        $this->memberFunctionAssertions(
            $userUserGroup,
            true,
            [
                ObjectHandler::GENERAL_ROLE_OBJECT_TYPE => [
                    1 => ObjectHandler::GENERAL_ROLE_OBJECT_TYPE
                ]
            ],
            ObjectHandler::GENERAL_USER_OBJECT_TYPE,
            3
        );
        $this->memberFunctionAssertions($userUserGroup, false, [], ObjectHandler::GENERAL_USER_OBJECT_TYPE, 5);

        // term tests
        $this->memberFunctionAssertions($termUserGroup, true, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions(
            $termUserGroup,
            true,
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']],
            'termObjectType',
            2
        );
        $this->memberFunctionAssertions($termUserGroup, true, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 3);
        $this->memberFunctionAssertions(
            $termUserGroup,
            true,
            [ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [1 => 'term']],
            ObjectHandler::GENERAL_TERM_OBJECT_TYPE,
            4
        );
        $this->memberFunctionAssertions($termUserGroup, false, [], ObjectHandler::GENERAL_TERM_OBJECT_TYPE, 5);

        // post tests
        $this->memberFunctionAssertions($postUserGroup, true, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 1);
        $this->memberFunctionAssertions(
            $postUserGroup,
            true,
            [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 'post'],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']
            ],
            'postObjectType',
            2
        );
        $this->memberFunctionAssertions(
            $postUserGroup,
            true,
            [
                ObjectHandler::GENERAL_POST_OBJECT_TYPE => [3 => 'post'],
                ObjectHandler::GENERAL_TERM_OBJECT_TYPE => [3 => 'term']
            ],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            2
        );
        $this->memberFunctionAssertions($postUserGroup, true, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 3);
        $this->memberFunctionAssertions(
            $postUserGroup,
            true,
            [ObjectHandler::GENERAL_POST_OBJECT_TYPE => [1 => 'post']],
            ObjectHandler::GENERAL_POST_OBJECT_TYPE,
            4
        );
        $this->memberFunctionAssertions($postUserGroup, false, [], ObjectHandler::GENERAL_POST_OBJECT_TYPE, 5);

        // pluggable object tests
        $this->memberFunctionAssertions($pluggableObjectUserGroup, false, [], 'noPluggableObject', 1);
        $this->memberFunctionAssertions(
            $pluggableObjectUserGroup,
            true,
            ['pluggableObject' => [1 => 'pluggableObject']],
            '_pluggableObject_',
            1
        );
        $this->memberFunctionAssertions($pluggableObjectUserGroup, false, [], '_pluggableObject_', 3);
    }

    /**
     * Generates return values.
     *
     * @param array $numbers
     *
     * @return array
     */
    private function generateUserReturn(array $numbers)
    {
        $returns = [];

        foreach ($numbers as $number) {
            $return = new \stdClass();
            $return->ID = $number;
            $returns[] = $return;
        }

        return $returns;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::getFullUsers()
     *
     * @return AbstractUserGroup
     */
    public function testGetFullUser()
    {
        $query = "SELECT ID, user_nicename FROM usersTable";

        $abstractUserGroup = $this->getTestIsUserMemberPrototype(
            ['user' => 2, 'role' => 3],
            [[new MatchIgnoreWhitespace($query)]],
            [$this->generateUserReturn([10 => 10, 1, 2, 3])],
            [
                [0, 2, ObjectHandler::GENERAL_ROLE_OBJECT_TYPE],
                [0, 1, ObjectHandler::GENERAL_ROLE_OBJECT_TYPE]
            ],
            1,
            3,
            4
        );

        self::assertEquals(
            [
                1 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                2 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                3 => ObjectHandler::GENERAL_USER_OBJECT_TYPE
            ],
            $abstractUserGroup->getFullUsers()
        );

        return $abstractUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::getFullTerms()
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::getFullObjects()
     *
     * @return AbstractUserGroup
     */
    public function testGetFullTerms()
    {
        $abstractUserGroup = $this->getTestIsTermMemberPrototype();
        self::assertEquals([1 => 'term', 2 => 'term', 3 => 'term'], $abstractUserGroup->getFullTerms());

        self::setValue($abstractUserGroup, 'fullObjectMembership', []);
        self::assertEquals([1 => 'term', 2 => 'term', 3 => 'term', 4 => 'term'], $abstractUserGroup->getFullTerms());

        return $abstractUserGroup;
    }

    /**
     * @group  unit
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::getFullPosts()
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::getFullObjects()
     *
     * @return AbstractUserGroup
     */
    public function testGetFullPosts()
    {
        $abstractUserGroup = $this->getTestIsPostMemberPrototype();
        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 9 => 'post'],
            $abstractUserGroup->getFullPosts()
        );

        self::setValue($abstractUserGroup, 'fullObjectMembership', []);
        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 4 => 'post', 9 => 'post'],
            $abstractUserGroup->getFullPosts()
        );

        return $abstractUserGroup;
    }

    /**
     * @group  unit
     * @depends testIsRoleMember
     * @depends testGetFullUser
     * @depends testGetFullTerms
     * @depends testGetFullPosts
     * @depends testIsPluggableObjectMember
     * @covers \UserAccessManager\UserGroup\AbstractUserGroup::getAssignedObjectsByType()
     *
     * @param AbstractUserGroup $roleUserGroup
     * @param AbstractUserGroup $userUserGroup
     * @param AbstractUserGroup $termUserGroup
     * @param AbstractUserGroup $postUserGroup
     * @param AbstractUserGroup $pluggableObjectUserGroup
     */
    public function testGetAssignedObjectsByType(
        AbstractUserGroup $roleUserGroup,
        AbstractUserGroup $userUserGroup,
        AbstractUserGroup $termUserGroup,
        AbstractUserGroup $postUserGroup,
        AbstractUserGroup $pluggableObjectUserGroup
    ) {
        self::assertEquals(
            [1 => 'role', 2 => 'role', 3 => 'role'],
            $roleUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_ROLE_OBJECT_TYPE)
        );

        self::assertEquals(
            [
                1 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                2 => ObjectHandler::GENERAL_USER_OBJECT_TYPE,
                3 => ObjectHandler::GENERAL_USER_OBJECT_TYPE
            ],
            $userUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_USER_OBJECT_TYPE)
        );

        self::assertEquals(
            [1 => 'term', 2 => 'term', 3 => 'term', 4 => 'term'],
            $termUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_TERM_OBJECT_TYPE)
        );
        self::setValue($termUserGroup, 'fullObjectMembership', ['termObjectType' => [1 => 'term', 2 => 'term']]);
        self::assertEquals(
            [1 => 'term', 2 => 'term'],
            $termUserGroup->getAssignedObjectsByType('termObjectType')
        );

        self::assertEquals(
            [1 => 'post', 2 => 'post', 3 => 'post', 4 => 'post', 9 => 'post'],
            $postUserGroup->getAssignedObjectsByType(ObjectHandler::GENERAL_POST_OBJECT_TYPE)
        );
        self::setValue($postUserGroup, 'fullObjectMembership', ['postObjectType' => [3 => 'post', 4 => 'post']]);
        self::assertEquals(
            [3 => 'post', 4 => 'post'],
            $postUserGroup->getAssignedObjectsByType('postObjectType')
        );

        self::assertEquals(
            [1 => 'pluggableObject', 6 => 'pluggableObject'],
            $pluggableObjectUserGroup->getAssignedObjectsByType('_pluggableObject_')
        );

        self::assertEquals(
            [],
            $pluggableObjectUserGroup->getAssignedObjectsByType('nothing')
        );
    }
}
