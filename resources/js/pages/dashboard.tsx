import { Head, router } from '@inertiajs/react';
import { useMemo, useState } from 'react';
import { Button } from '@/components/ui/button';
import { Input } from '@/components/ui/input';
import { dashboard } from '@/routes';
import walletRoutes from '@/routes/wallet';

type DashboardPageProps = {
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

export default function Dashboard({ wallet, transactions }: DashboardPageProps) {
    const [showDepositModal, setShowDepositModal] = useState(false);
    const [showTransferModal, setShowTransferModal] = useState(false);
    const [depositAmount, setDepositAmount] = useState('');
    const [transferAmount, setTransferAmount] = useState('');
    const [destinationKey, setDestinationKey] = useState('');
    const [depositKey, setDepositKey] = useState(() => createIdempotencyKey());
    const [transferKey, setTransferKey] = useState(() => createIdempotencyKey());
    const [isSubmittingDeposit, setIsSubmittingDeposit] = useState(false);
    const [isSubmittingTransfer, setIsSubmittingTransfer] = useState(false);

    const walletBalance = wallet?.balance ?? 0;
    const walletKey = wallet?.wallet_transaction_key ?? '—';

    const sortedTransactions = useMemo(
        () => [...transactions].sort((a, b) => Number(b.id) - Number(a.id)).slice(0, 6),
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
                    setShowDepositModal(false);
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
                    setShowTransferModal(false);
                },
                onError: () => {
                    setIsSubmittingTransfer(false);
                },
            },
        );
    }

    return (
        <>
            <Head title="Dashboard" />

            <div className="space-y-6 p-4">
                <div className="rounded-xl border bg-card p-6 shadow-sm">
                    <div className="flex flex-col gap-4 md:flex-row md:items-end md:justify-between">
                        <div>
                            <p className="text-sm text-muted-foreground">Saldo atual</p>
                            <h1 className="mt-2 text-3xl font-semibold">{formatCurrency(walletBalance)}</h1>
                        </div>

                        <div className="flex flex-wrap gap-3">
                            <Button onClick={() => setShowDepositModal(true)}>Depositar</Button>
                            <Button variant="outline" onClick={() => setShowTransferModal(true)}>Transferir</Button>
                        </div>
                    </div>

                    <div className="mt-4 rounded-md bg-muted p-3 text-sm">
                        <span className="font-medium">Chave de recebimento:</span> {walletKey}
                    </div>
                </div>

                <div className="rounded-xl border bg-card p-4 shadow-sm">
                    <div className="mb-4 flex items-center justify-between">
                        <h2 className="text-lg font-semibold">Movimentações recentes</h2>
                    </div>

                    <div className="space-y-2">
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

            {showDepositModal ? (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-xl border bg-card p-6 shadow-lg">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-lg font-semibold">Novo depósito</h3>
                            <Button variant="ghost" size="sm" onClick={() => setShowDepositModal(false)}>Fechar</Button>
                        </div>

                        <form onSubmit={handleDepositSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <label htmlFor="dashboard-deposit" className="text-sm font-medium">Valor</label>
                                <Input
                                    id="dashboard-deposit"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value={depositAmount}
                                    onChange={(event) => setDepositAmount(event.target.value)}
                                    placeholder="10,00"
                                />
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="outline" onClick={() => setShowDepositModal(false)}>Cancelar</Button>
                                <Button type="submit" disabled={isSubmittingDeposit || !depositAmount}>
                                    {isSubmittingDeposit ? 'Processando...' : 'Confirmar'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            ) : null}

            {showTransferModal ? (
                <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/50 p-4">
                    <div className="w-full max-w-md rounded-xl border bg-card p-6 shadow-lg">
                        <div className="mb-4 flex items-center justify-between">
                            <h3 className="text-lg font-semibold">Nova transferência</h3>
                            <Button variant="ghost" size="sm" onClick={() => setShowTransferModal(false)}>Fechar</Button>
                        </div>

                        <form onSubmit={handleTransferSubmit} className="space-y-4">
                            <div className="space-y-2">
                                <label htmlFor="dashboard-transfer-key" className="text-sm font-medium">Chave da carteira destino</label>
                                <Input
                                    id="dashboard-transfer-key"
                                    value={destinationKey}
                                    onChange={(event) => setDestinationKey(event.target.value)}
                                    placeholder="wallet-123"
                                />
                            </div>

                            <div className="space-y-2">
                                <label htmlFor="dashboard-transfer-amount" className="text-sm font-medium">Valor</label>
                                <Input
                                    id="dashboard-transfer-amount"
                                    type="number"
                                    min="0.01"
                                    step="0.01"
                                    value={transferAmount}
                                    onChange={(event) => setTransferAmount(event.target.value)}
                                    placeholder="25,00"
                                />
                            </div>

                            <div className="flex justify-end gap-2">
                                <Button type="button" variant="outline" onClick={() => setShowTransferModal(false)}>Cancelar</Button>
                                <Button type="submit" disabled={isSubmittingTransfer || !transferAmount || !destinationKey}>
                                    {isSubmittingTransfer ? 'Enviando...' : 'Confirmar'}
                                </Button>
                            </div>
                        </form>
                    </div>
                </div>
            ) : null}
        </>
    );
}

Dashboard.layout = {
    breadcrumbs: [
        {
            title: 'Dashboard',
            href: dashboard(),
        },
    ],
};
