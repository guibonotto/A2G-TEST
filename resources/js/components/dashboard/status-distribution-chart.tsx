import { Cell, Pie, PieChart, ResponsiveContainer, Tooltip } from 'recharts';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { statusColorVar } from '@/lib/status-color';
import type { StatusBreakdownItem } from '@/types/dashboard';

export function StatusDistributionChart({ data }: { data: StatusBreakdownItem[] }) {
    const total = data.reduce((sum, item) => sum + item.total, 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Distribuição por Status</CardTitle>
                <CardDescription>Casos de teste agrupados pelo status atual</CardDescription>
            </CardHeader>
            <CardContent>
                {total === 0 ? (
                    <p className="py-12 text-center text-sm text-muted-foreground">
                        Nenhum caso de teste cadastrado ainda.
                    </p>
                ) : (
                    <div className="relative">
                        <ResponsiveContainer width="100%" height={280}>
                            <PieChart>
                                <Pie
                                    data={data}
                                    dataKey="total"
                                    nameKey="name"
                                    innerRadius={70}
                                    outerRadius={100}
                                    paddingAngle={2}
                                    cornerRadius={4}
                                    stroke="var(--card)"
                                    strokeWidth={2}
                                >
                                    {data.map((item) => (
                                        <Cell key={item.id} fill={statusColorVar(item.color)} />
                                    ))}
                                </Pie>
                                <Tooltip
                                    formatter={(value: number, name: string) => [`${value} caso(s)`, name]}
                                    contentStyle={{
                                        background: 'var(--popover)',
                                        borderColor: 'var(--border)',
                                        borderRadius: 8,
                                        color: 'var(--popover-foreground)',
                                        fontSize: 12,
                                    }}
                                />
                            </PieChart>
                        </ResponsiveContainer>

                        <div className="pointer-events-none absolute inset-0 flex flex-col items-center justify-center">
                            <span className="text-2xl font-semibold tabular-nums">{total}</span>
                            <span className="text-xs text-muted-foreground">casos</span>
                        </div>
                    </div>
                )}

                <ul className="mt-4 flex flex-wrap gap-x-4 gap-y-2">
                    {data.map((item) => (
                        <li key={item.id} className="flex items-center gap-1.5 text-xs text-muted-foreground">
                            <span className="size-2.5 rounded-full" style={{ backgroundColor: statusColorVar(item.color) }} />
                            {item.name} ({item.total})
                        </li>
                    ))}
                </ul>
            </CardContent>
        </Card>
    );
}