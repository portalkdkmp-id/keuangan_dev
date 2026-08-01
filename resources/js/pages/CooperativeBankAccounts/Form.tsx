import { MasterBankAccountForm } from '@/components/BankAccounts/MasterBankAccountForm';
export default function Form({ account, cooperatives }: any) { return <MasterBankAccountForm title={account ? 'Edit Rekening Koperasi' : 'Tambah Rekening Koperasi'} baseUrl="/cooperative-bank-accounts" account={account} cooperatives={cooperatives} />; }
