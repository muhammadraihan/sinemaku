'use strict';
const {test}=require('node:test');
const assert=require('node:assert/strict');
const worker=require('../scripts/cinepoint-push-snapshot.cjs');
test('signature binds method path body and delivery',()=>{
 const headers=worker.sign('POST','/api/internal/cinepoint/snapshots','{}','a'.repeat(32),'test-key','test-only-secret',1700000000);
 const crypto=require('crypto');
 const hash=crypto.createHash('sha256').update('{}').digest('hex');
 assert.equal(headers['X-Cinepoint-Signature'],crypto.createHmac('sha256','test-only-secret').update('POST\n/api/internal/cinepoint/snapshots\n1700000000\n'+'a'.repeat(32)+'\n'+hash).digest('hex'));
});
test('retry retains exact bytes and delivery and never recollects',async()=>{
 let calls=[];
 await worker.retrySend({path:'/snapshots',body:'{}',delivery:'a'.repeat(32)},async e=>{calls.push({...e}); if(calls.length<3) throw Object.assign(new Error(),{retryable:true}); return {};},async()=>{});
 assert.equal(calls.length,3); assert.deepEqual(calls[0],calls[2]);
});
test('poll reports only collection failures and not upload ambiguity',async()=>{
 const sent=[]; const claim={job:{id:4,lease_token:'lease'}};
 await assert.rejects(worker.runPoll(async(path,payload)=>{sent.push({path,payload}); if(path.endsWith('claim'))return claim;throw new Error('network');},async()=>({source_total:1,entries:[{}]})),/network/);
 assert.equal(sent.length,2); assert.equal(sent[1].payload.status,'success');
 const failed=[];
 await assert.rejects(worker.runPoll(async(path,payload)=>{failed.push(payload);return claim;},async()=>{throw new Error('sensitive browser stderr');}),/browser_failed/);
 assert.equal(failed[1].error_code,'browser_failed'); assert.ok(!JSON.stringify(failed).includes('sensitive'));
});
test('reject unsafe endpoints and malformed browser stdout',()=>{
 assert.throws(()=>worker.endpoint('http://example.test/api'));
 assert.throws(()=>worker.endpoint('https://user:pass@example.test/api'));
 assert.throws(()=>worker.validate({source_total:2,entries:[]}));
});
