import { MasterBankAccountList } from '@/components/BankAccounts/MasterBankAccountList';
export default function Index({ accounts }: any) { return <MasterBankAccountList title="Rekening Koperasi" baseUrl="/cooperative-bank-accounts" accounts={accounts} cooperative />; }
