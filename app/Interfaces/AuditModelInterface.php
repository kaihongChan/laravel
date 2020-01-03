<?php


namespace App\Interfaces;

interface AuditModelInterface
{
    /**
     * 提交审核回调
     *
     * @param $requestData
     * @return mixed
     */
    public function submitCallback(array $requestData = []);

    /**
     * 流转回调
     *
     * @param $currentId
     * @param array $requestData
     * @return mixed
     */
    public function circulateCallback(int $currentId, array $requestData = []);

    /**
     * 通过回调
     *
     * @param array $requestData
     * @return mixed
     */
    public function passCallback(array $requestData = []);

    /**
     * 驳回回调
     *
     * @param array $requestData
     * @return mixed
     */
    public function rejectCallback(array $requestData = []);


}