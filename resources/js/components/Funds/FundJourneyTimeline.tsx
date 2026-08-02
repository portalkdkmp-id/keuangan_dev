import { Check, Circle, Clock3 } from 'lucide-react';
import { rupiah } from '@/components/Submissions/SubmissionSummary';
import { formatDate } from '@/lib/format';

export function FundJourneyTimeline({ submission }: any) {
    const disbursement=submission.disbursement; const distributions=disbursement?.distributions??[]; const receipt=submission.receipt_confirmations?.[0]; const report=submission.accountability_report;
    const steps=[
        {title:'Director Approved',done:!!submission.director_decided_at,date:submission.director_decided_at,actor:submission.director_decision_maker?.name,amount:submission.director_approved_amount},
        {title:'Director Disbursed',done:!!disbursement,date:disbursement?.transferred_at,actor:disbursement?.disburser?.name,amount:disbursement?.amount,note:disbursement?`Penerima pertama: ${disbursement.recipient_name_snapshot}`:null},
        {title:'Finance Staff Distributed',done:!disbursement?.requires_distribution||distributions.length>0,date:distributions.at(-1)?.transferred_at,actor:distributions.at(-1)?.distributor?.name,amount:distributions.reduce((sum:number,item:any)=>sum+Number(item.amount),0),skip:disbursement&&!disbursement.requires_distribution},
        {title:'PIC Confirmed Receipt',done:!!receipt,date:receipt?.received_at,actor:receipt?.recipient?.name,amount:receipt?.amount},
        {title:'Accountability Submitted',done:!!report?.submitted_at,date:report?.submitted_at,actor:report?.submitter?.name,amount:report?.realized_amount},
        {title:'Finance Verified',done:['finance_verified','approved','closed'].includes(report?.status),date:report?.finance_reviewed_at,actor:report?.finance_reviewer?.name},
        {title:'Approval Approved',done:['approved','closed'].includes(report?.status),date:report?.approved_at,actor:report?.approver?.name},
        {title:'Closed',done:report?.status==='closed',date:report?.closed_at},
    ].filter(step=>!step.skip);
    return <div className="space-y-0">{steps.map((step:any,index:number)=><div key={step.title} className="relative flex gap-3 pb-6 last:pb-0">{index<steps.length-1&&<div className="absolute top-7 bottom-0 left-[13px] w-px bg-border"/>}<div className={`relative z-10 flex size-7 shrink-0 items-center justify-center rounded-full border ${step.done?'border-emerald-600 bg-emerald-600 text-white':'bg-background text-muted-foreground'}`}>{step.done?<Check className="size-4"/>:index===steps.findIndex((item:any)=>!item.done)?<Clock3 className="size-4"/>:<Circle className="size-3"/>}</div><div className="min-w-0 pt-0.5"><div className="font-medium">{step.title}</div><div className="text-sm text-muted-foreground">{step.done?formatDate(step.date):'Belum selesai'}{step.actor?` · ${step.actor}`:''}{step.amount?` · ${rupiah(step.amount)}`:''}</div>{step.note&&<div className="mt-1 text-sm">{step.note}</div>}</div></div>)}</div>;
}
