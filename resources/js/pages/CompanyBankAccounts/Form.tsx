import { MasterBankAccountForm } from '@/components/BankAccounts/MasterBankAccountForm';
export default function Form({ account }: any) { return <MasterBankAccountForm title={account ? 'Edit Rekening Perusahaan' : 'Tambah Rekening Perusahaan'} baseUrl="/company-bank-accounts" account={account} company />; }
