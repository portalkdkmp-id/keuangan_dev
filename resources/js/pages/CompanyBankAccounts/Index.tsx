import { MasterBankAccountList } from '@/components/BankAccounts/MasterBankAccountList';
export default function Index({ accounts }: any) { return <MasterBankAccountList title="Rekening Perusahaan" baseUrl="/company-bank-accounts" accounts={accounts} />; }
