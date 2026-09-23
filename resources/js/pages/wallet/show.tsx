import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import walletRoutes from '@/routes/wallet';

type WalletPageProps = {
    wallet: {
        id: number;
        balance: number;
        wallet_transaction_key: string;
    } | null;
    transactions: Array<{
        id: number;
        type: string;
        amount: number;
        created_at: string | null;
    }>;
};

function formatCurrency(value: number): string {
    return new Intl.NumberFormat('pt-BR', {
        style: 'currency',
        currency: 'BRL',
    }).format(value / 100);
}

function createIdempotencyKey(): string {
    return crypto.randomUUID();
}

export default function WalletShow({ wallet, transactions }: WalletPageProps) {
    const [depositAmount, setDepositAmount] = useState('');
    const [transferAmount, setTransferAmount] = useState('');
    const [destinationKey, setDestinationKey] = useState('');
    const [depositKey, setDepositKey] = useState(() => createIdempotencyKey());
    const [transferKey, setTransferKey] = useState(() => createIdempotencyKey());
    const [isSubmittingDeposit, setIsSubmittingDeposit] = useState(false);
    const [isSubmittingTransfer, setIsSubmittingTransfer] = useState(false);

    const walletBalance = wallet?.balance ?? 0;
    const walletKey = wallet?.wallet_transaction_key ?? '';

    const sortedTransactions = useMemo(
        () => [...transactions].sort((a, b) => Number(b.id) - Number(a.id)),
        [transactions],
    );

    function handleDepositSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!depositAmount || Number(depositAmount) <= 0) {
            return;
        }

        setIsSubmittingDeposit(true);

        router.post(
            walletRoutes.deposit.url(),
            {
                amount: depositAmount,
                idempotency_key: depositKey,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setDepositAmount('');
                    setDepositKey(createIdempotencyKey());
                    setIsSubmittingDeposit(false);
                },
                onError: () => {
                    setIsSubmittingDeposit(false);
                },
            },
        );
    }

    function handleTransferSubmit(event: React.FormEvent<HTMLFormElement>) {
        event.preventDefault();

        if (!transferAmount || Number(transferAmount) <= 0 || !destinationKey) {
            return;
        }

        setIsSubmittingTransfer(true);

        router.post(
            walletRoutes.transfer.url(),
            {
                amount: transferAmount,
                wallet_transaction_key: destinationKey,
                idempotency_key: transferKey,
            },
            {
                preserveScroll: true,
                onSuccess: () => {
                    setTransferAmount('');
                    setDestinationKey('');
                    setTransferKey(createIdempotencyKey());
                    setIsSubmittingTransfer(false);
                },
                onError: () => {
                    setIsSubmittingTransfer(false);
                },
            },
        );
    }

    return (
        <>
            <Head title="Wallet" />

            <div className="space-y-6 p-4">
                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <p className="text-sm text-muted-foreground">Saldo atual</p>
                    <h1 className="mt-2 text-3xl font-semibold">{formatCurrency(walletBalance)}</h1>

                    <div className="mt-4 rounded-md bg-muted p-3 text-sm">
                        <span className="font-medium">Chave de recebimento:</span> {walletKey || '—'}
                    </div>
                </div>

                <div className="grid gap-6 md:grid-cols-2">
                    <form onSubmit={handleDepositSubmit} className="rounded-xl border bg-card p-4 shadow-sm space-y-4">
                        <h2 className="text-lg font-semibold">Depósito</h2>

                        <div className="space-y-2">
                            <label htmlFor="deposit-amount" className="text-sm font-medium">Valor</label>
                            <Input
                                id="deposit-amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                value={depositAmount}
                                onChange={(event) => setDepositAmount(event.target.value)}
                                placeholder="10,00"
                            />
                        </div>

                        <Button type="submit" disabled={isSubmittingDeposit || !depositAmount}>
                            {isSubmittingDeposit ? 'Processando...' : 'Depositar'}
                        </Button>
                    </form>

                    <form onSubmit={handleTransferSubmit} className="rounded-xl border bg-card p-4 shadow-sm space-y-4">
                        <h2 className="text-lg font-semibold">Transferência</h2>

                        <div className="space-y-2">
                            <label htmlFor="destination-key" className="text-sm font-medium">Chave da carteira destino</label>
                            <Input
                                id="destination-key"
                                value={destinationKey}
                                onChange={(event) => setDestinationKey(event.target.value)}
                                placeholder="wallet-123"
                            />
                        </div>

                        <div className="space-y-2">
                            <label htmlFor="transfer-amount" className="text-sm font-medium">Valor</label>
                            <Input
                                id="transfer-amount"
                                type="number"
                                min="0.01"
                                step="0.01"
                                value={transferAmount}
                                onChange={(event) => setTransferAmount(event.target.value)}
                                placeholder="25,00"
                            />
                        </div>

                        <Button type="submit" disabled={isSubmittingTransfer || !transferAmount || !destinationKey}>
                            {isSubmittingTransfer ? 'Enviando...' : 'Transferir'}
                        </Button>
                    </form>
                </div>

                <div className="rounded-xl border bg-card p-4 shadow-sm">
                    <h2 className="text-lg font-semibold">Histórico</h2>

                    <div className="mt-4 space-y-2">
                        {sortedTransactions.length === 0 ? (
                            <p className="text-sm text-muted-foreground">Nenhuma movimentação registrada.</p>
                        ) : (
                            sortedTransactions.map((transaction) => (
                                <div key={transaction.id} className="flex items-center justify-between rounded-md border p-3">
                                    <div>
                                        <p className="font-medium capitalize">{transaction.type}</p>
                                        <p className="text-xs text-muted-foreground">
                                            {transaction.created_at ? new Date(transaction.created_at).toLocaleString('pt-BR') : '—'}
                                        </p>
                                    </div>
                                    <span className={transaction.amount >= 0 ? 'text-emerald-600' : 'text-red-600'}>
                                        {formatCurrency(transaction.amount)}
                                    </span>
                                </div>
                            ))
                        )}
                    </div>
                </div>
            </div>
        </>
    );
}

WalletShow.layout = {
    breadcrumbs: [
        {
            title: 'Wallet',
            href: walletRoutes.show(),
        },
    ],
};
