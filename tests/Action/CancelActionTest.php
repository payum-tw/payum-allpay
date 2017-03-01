<?php

namespace PayumTW\Allpay\Tests\Action;

use Mockery as m;
use Payum\Core\Request\Cancel;
use PHPUnit\Framework\TestCase;
use Payum\Core\Bridge\Spl\ArrayObject;
use PayumTW\Allpay\Action\CancelAction;

class CancelActionTest extends TestCase
{
    protected function tearDown()
    {
        m::close();
    }

    public function testExecute()
    {
        $action = new CancelAction();
        $request = new Cancel(new ArrayObject([]));

        $action->setGateway(
            $gateway = m::mock('Payum\Core\GatewayInterface')
        );

        $gateway->shouldReceive('execute')->once()->with(m::type('PayumTW\Allpay\Request\Api\CancelTransaction'));

        $action->execute($request);

        $this->assertSame([], (array) $request->getModel());
    }
}
