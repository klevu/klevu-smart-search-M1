<?php

class Klevu_Search_Test_Model_Sync extends EcomDev_PHPUnit_Test_Case {

    const TEST_JOB_CODE = "klevu_search_test_job";

    protected function tearDown() {
        $collection = $this->getTestCronScheduleCollection();
        foreach ($collection as $item) {
            $item->delete();
        }

        parent::tearDown();
    }

    /**
     * @test
     * @dataProvider dataProvider
     */
    public function testSchedule($time) {
        $model = $this->getTestModel();

        $time = new DateTime($time);

        $model->schedule($time);

        $collection = $this->getTestCronScheduleCollection();

        $this->assertEquals(1, $collection->getSize());

        $schedule = $collection->getFirstItem();

        $this->assertEquals($time->format("Y-m-d H:i:00"), $schedule->getScheduledAt());
    }

    /**
     * @test
     */
    public function testAlreadyScheduled() {
        $model = $this->getTestModel();
        $connection = Mage::getModel('core/resource')->getConnection("core_write");
        $count_sql = $this->getTestCronScheduleCollection()->getSelectCountSql();

        $time = new DateTime("now");

        $model->schedule($time);
        $this->assertEquals(1, $connection->fetchOne($count_sql),
            "Failed to assert that schedule() adds a cron entry to the schedule.");

        $before = clone $time;
        $before->modify("15 minutes ago");
        $model->schedule($before);
        $this->assertEquals(1, $connection->fetchOne($count_sql),
            "Failed to assert that schedule() does not add a new cron entry if one is already scheduled for up to 15 minutes later.");

        $after = clone $time;
        $after->modify("15 minutes");
        $model->schedule($after);
        $this->assertEquals(1, $connection->fetchOne($count_sql),
            "Failed to assert that schedule() does not add a new cron entry if one is already scheduled for up to 15 minutes earlier.");

        $before->modify("5 minutes ago");
        $model->schedule($before);
        $this->assertEquals(2, $connection->fetchOne($count_sql),
            "Failed to assert that schedule() can add a new cron entry 20 minutes before an existing one.");

        $after->modify("5 minutes");
        $model->schedule($after);
        $this->assertEquals(3, $connection->fetchOne($count_sql),
            "Failed to assert that schedule() can add a new cron entry 20 minutes after an existing one.");
    }

    /**
     * @test
     */
    public function testIsRunning() {
        $model = $this->getTestModel();

        $now = new DateTime();
        $now = $now->format("Y-m-d H:i:00");

        $schedule = Mage::getModel('cron/schedule');
        $schedule
            ->setJobCode(static::TEST_JOB_CODE)
            ->setCreatedAt($now)
            ->setScheduledAt($now)
            ->setExecutedAt($now)
            ->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING)
            ->save();

        $this->assertTrue($model->isRunning(), "Failed to assert that isRunning() returns true when there's a sync cron running.");
        $this->assertFalse($model->isRunning(2), "Failed to assert that isRunning(2) returns false when there's only one cron running.");


        $schedule = Mage::getModel('cron/schedule');
        $schedule
            ->setJobCode(static::TEST_JOB_CODE)
            ->setCreatedAt($now)
            ->setScheduledAt($now)
            ->setExecutedAt($now)
            ->setStatus(Mage_Cron_Model_Schedule::STATUS_RUNNING)
            ->save();

        $this->assertTrue($model->isRunning(2), "Failed to assert that isRunning(2) returns true when there's 2 sync crons running.");
    }

    /**
     * Return a Mock fo the klevu_search/sync model for testing.
     *
     * @return PHPUnit_Framework_MockObject_MockObject
     */
    protected function getTestModel() {
        $mock = $this->getMockForAbstractClass(Mage::app()->getConfig()->getModelClassName("klevu_search/sync"));
        $mock
            ->expects($this->any())
            ->method("getJobCode")
            ->will($this->returnValue(static::TEST_JOB_CODE));
        return $mock;
    }

    /**
     * Return a cron/schedule collection filtered for test jobs only.
     *
     * @return Mage_Cron_Model_Mysql4_Schedule_Collection
     */
    protected function getTestCronScheduleCollection() {
        return Mage::getResourceModel('cron/schedule_collection')
            ->addFieldToFilter("job_code", static::TEST_JOB_CODE);
    }
}
