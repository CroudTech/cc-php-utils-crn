<?php
// declare(strict_types=1);

use PHPUnit\Framework\TestCase;
use CroudTech\CrnMapper;

class CrnMapperTest extends TestCase
{

    protected $mapper;

    public function setUp()
    {
        putenv('SYSTEM_ID=a7c5310f-2e38-49b4-99b7-a57482b0aacd');
        putenv('SERVICE_NAME=test-service');
        putenv('INTERNAL_ROUTING_FORMAT=http://<serviceName>.<serviceNamespace>.svc.cluster.local');
        putenv('SERVICE_MAP={"systems":{"a7c5310f-2e38-49b4-99b7-a57482b0aacd":{"domain":"https://<serviceName>.example.com","namespace":"stg-v3-tennant","aliases":{"activity-log":"activity-log-django",    "file-service":"files","user-service":"user-service-django","test-service":"test-service-django"}}}}');  
        $this->mapper = new CrnMapper;
    }

    public function testValidPublicUrlFromCrn()
    {
        $crn = 'a7c5310f-2e38-49b4-99b7-a57482b0aacd:file-service:files:cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $expectedUrl = 'https://file-service.example.com/files/cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $this->assertEquals($expectedUrl, $this->mapper->publicUrlFromCrn($crn));
    }

    public function testValidNestedPublicUrlFromCrn()
    {
        $crn = 'a7c5310f-2e38-49b4-99b7-a57482b0aacd:file-service:tasks:a7c5310f-2e38-49b4-99b7-a57482b0aacd:files:cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $expectedUrl = 'https://file-service.example.com/tasks/a7c5310f-2e38-49b4-99b7-a57482b0aacd/files/cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $this->assertEquals($expectedUrl, $this->mapper->publicUrlFromCrn($crn));
    }

    function testValidInternalUrlFromCrn()
    {
        $crn = 'a7c5310f-2e38-49b4-99b7-a57482b0aacd:file-service:files:cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $expectedUrl = 'http://files.stg-v3-tennant.svc.cluster.local/files/cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $this->assertEquals($expectedUrl, $this->mapper->internalUrlFromCrn($crn));
    }

    function testValidNestedInternalUrlFromCrn()
    {
        $crn = 'a7c5310f-2e38-49b4-99b7-a57482b0aacd:file-service:tasks:a7c5310f-2e38-49b4-99b7-a57482b0aacd:files:cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $expectedUrl = 'http://files.stg-v3-tennant.svc.cluster.local/tasks/a7c5310f-2e38-49b4-99b7-a57482b0aacd/files/cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $this->assertEquals($expectedUrl, $this->mapper->internalUrlFromCrn($crn));
    }

    function testSimpleCrn()
    {
        $params = [
            [
                'entity' => 'files',
                'id' => 'cdd4bb32-258e-40d7-8255-4e2e1526e60c',
            ],
        ];
        $expectedCrn = 'a7c5310f-2e38-49b4-99b7-a57482b0aacd:test-service:files:cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $this->assertEquals($expectedCrn, $this->mapper->createCrn($params));
    }

    function testNestedSimpleCrn()
    {
        $params = [
            [
                'entity' => 'tasks',
                'id' => 'cdd4bb32-258e-40d7-8255-4e2e1526e60c',
            ],
            [
                'entity' => 'files',
                'id' => 'a7c5310f-2e38-49b4-99b7-a57482b0aacd',
            ],
        ];
        $expectedCrn = 'a7c5310f-2e38-49b4-99b7-a57482b0aacd:test-service:tasks:cdd4bb32-258e-40d7-8255-4e2e1526e60c:files:a7c5310f-2e38-49b4-99b7-a57482b0aacd';
        $this->assertEquals($expectedCrn, $this->mapper->createCrn($params));
    }

    function testInvalidSystem()
    {
        $crn = 'a7c5310f-2e38-49b4-99b7-a57482b032213d:file-service:tasks:a7c5310f-2e38-49b4-99b7-a57482b0aacd:files:cdd4bb32-258e-40d7-8255-4e2e1526e132s';
        $expectedMessage = 'Invalid System ID';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->mapper->publicUrlFromCrn($crn);
    }

    function testInvalidAlias()
    {
        $crn = 'a7c5310f-2e38-49b4-99b7-a57482b0aacd:file-servicesss:files:cdd4bb32-258e-40d7-8255-4e2e1526e60c';
        $expectedMessage = 'Service alias not found';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->mapper->internalUrlFromCrn($crn);
    }

    public function testInvalidCrnEntity()
    {
        $params = [
            [
                'entitysad' => 'files',
                'id' => 'cdd4bb32-258e-40d7-8255-4e2e1526e60c',
            ],
        ];
        $expectedMessage = 'Incorrect paramter definition';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->mapper->createCrn($params);
    }

    public function testInvalidCrnId()
    {
        $params = [
            [
                'entitys' => 'files',
                'idsss' => 'cdd4bb32-258e-40d7-8255-4e2e1526e60c',
            ],
        ];
        $expectedMessage = 'Incorrect paramter definition';
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage($expectedMessage);
        $this->mapper->createCrn($params);
    }
}
