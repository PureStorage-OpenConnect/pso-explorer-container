<?php

namespace App\Http\Controllers;

use App\Http\Classes\PsoArray;
use App\Http\Classes\PsoNode;
use App\Http\Requests\NodeAgentRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class NodeAgentController extends Controller
{

    public function getInfo(Request $request)
    {
        $details['ips'] = [];
        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $item) {
            $myarray = new PsoArray($item);
            if (count($myarray->iSCSIEndpoints ?? []) > 0) {
                foreach ($myarray->iSCSIEndpoints as $iSCSIEndpoint) {
                    array_push($details['ips'], $iSCSIEndpoint);
                }
            }
            if (count($myarray->nfsEndpoints ?? []) > 0) {
                foreach ($myarray->nfsEndpoints as $nfsEndpoints) {
                    array_push($details['ips'], $nfsEndpoints);
                }
            }
        }
        return $details;
    }

    public function postInfo(NodeAgentRequest $request)
    {
        $uid = PsoNode::getUidByNodeName($request->input('node'));
        if ($uid) {
            $node = new PsoNode($uid);
            $pingStatus = $node->pingStatus;
            $pingStatus[$request->input('ip')] = $request->input('result');
            $node->pingStatus = $pingStatus;
            if (!$request->input('result')) {
                $node->pingErrors = true;
            }
        }
    }
}
