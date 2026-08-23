import { Bar, BarChart, CartesianGrid, Cell, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { ClassificationBreakdownItem } from '@/types/dashboard';

const SERIES_COLORS = ['var(--chart-1)', 'var(--chart-2)', 'var(--chart-3)', 'var(--chart-4)', 'var(--chart-5)'];

export function ClassificationChart({ data }: { data: ClassificationBreakdownItem[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Casos por Classificação</CardTitle>
                <CardDescription>Unitário vs. integração e demais classificações cadastradas</CardDescription>
            </CardHeader>
            <CardContent>
                {data.length === 0 ? (
                    <p className="py-12 text-center text-sm text-muted-foreground">Nenhuma classificação cadastrada ainda.</p>
                ) : (
                    <ResponsiveContainer width="100%" height={Math.max(120, data.length * 44)}>
                        <BarChart data={data} layout="vertical" margin={{ left: 8, right: 16 }}>
                            <CartesianGrid horizontal={false} stroke="var(--border)" strokeDasharray="3 3" />
                            <XAxis type="number" allowDecimals={false} stroke="var(--muted-foreground)" fontSize={12} />
                            <YAxis type="category" dataKey="name" width={110} stroke="var(--muted-foreground)" fontSize={12} tickLine={false} axisLine={false} />
                            <Tooltip
                                cursor={{ fill: 'var(--muted)' }}
                                formatter={(value: number) => [`${value} caso(s)`, 'Total']}
                                contentStyle={{ background: 'var(--popover)', borderColor: 'var(--border)', borderRadius: 8, fontSize: 12 }}
                            />
                            <Bar dataKey="total" radius={[0, 4, 4, 0]} barSize={20}>
                                {data.map((item, index) => (
                                    <Cell key={item.id} fill={SERIES_COLORS[index % SERIES_COLORS.length]} />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                )}
            </CardContent>
        </Card>
    );
}