<?php

namespace App\Http\Controllers;

use App\Http\Classes\PsoArray;
use App\Http\Classes\PsoNode;
use App\Http\Requests\NodeAgentGetRequest;
use App\Http\Requests\NodeAgentPostRequest;
use App\Pso;
use Illuminate\Support\Facades\Log;

class NodeAgentController extends Controller
{

    public function getInfo(NodeAgentGetRequest $request)
    {
        // Check if node name is in database
        $nodeName = $request->input('node');
        $uid = PsoNode::getUidByNodeName($nodeName);
        if ($uid == null) {
            // If the node name is unknown exit
            return null;
        }

        // Get/update PSO information
        $pso = new Pso();

        // Get all FA/FB management IP's
        $mgmtIps = [];
        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $item) {
            array_push($mgmtIps, $item);
        }

        // Get all FA iSCSI and FB NFS IP's
        $iscsiIps = [];
        $nfsIps = [];
        foreach (PsoArray::items(PsoArray::PREFIX, 'mgmtEndPoint') as $item) {
            $myarray = new PsoArray($item);
            if (count($myarray->iSCSIEndpoints ?? []) > 0) {
                foreach ($myarray->iSCSIEndpoints as $iSCSIEndpoint) {
                    array_push($iscsiIps, $iSCSIEndpoint);
                }
            }
            if (count($myarray->nfsEndpoints ?? []) > 0) {
                foreach ($myarray->nfsEndpoints as $nfsEndpoints) {
                    array_push($nfsIps, $nfsEndpoints);
                }
            }
        }

        $details['ips'] = [];
        if ($pso->psoInfo->psoEdition == 'PSO6') {
            // PSO6+ uses attach/detach so only the provisioner node needs management access
            if ($pso->psoInfo->psoProvisionerNode == $nodeName) {
                $details['ips'] = $mgmtIps;
            }
        } else {
            // For pre-PSO6 all nodes need access to management
            $details['ips'] = $mgmtIps;
        }

        // If PSO deamonset is active on this node, add iSCSI and NFS IP's to be checked
        if (in_array($nodeName, $pso->psoInfo->psoNodes)) {
            if (strtoupper($pso->psoInfo->sanType) == 'ISCSI') {
                // If backend is iSCSI check iSCSI and NFS connectivity
                $details['ips'] = array_merge($details['ips'], $iscsiIps, $nfsIps);
            } else {
                // If backend is FC check NFS connectivity only
                $details['ips'] = array_merge($details['ips'], $nfsIps);
            }
        }

        // Record node agent check-in
        $node = new PsoNode($uid);
        $pingStatus = $node->pingStatus;
        $pingStatus['Node agent'] = true;

        foreach ($details['ips'] as $ip) {
            $pingStatus[$ip] = 'Unknown';
        }
        $node->pingStatus = $pingStatus;

        return $details;
    }

    public function postInfo(NodeAgentPostRequest $request)
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
