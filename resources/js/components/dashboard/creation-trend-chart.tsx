import { Area, AreaChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { CreationTrendPoint } from '@/types/dashboard';

function formatDay(value: string) {
    return new Date(`${value}T00:00:00`).toLocaleDateString('en-US', { day: '2-digit', month: '2-digit' });
}

export function CreationTrendChart({ data }: { data: CreationTrendPoint[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Test Cases Created</CardTitle>
                <CardDescription>Cumulative total over the last 30 days</CardDescription>
            </CardHeader>
            <CardContent>
                <ResponsiveContainer width="100%" height={260}>
                    <AreaChart data={data} margin={{ left: 8, right: 16, top: 8 }}>
                        <defs>
                            <linearGradient id="creationTrendFill" x1="0" y1="0" x2="0" y2="1">
                                <stop offset="0%" stopColor="var(--chart-1)" stopOpacity={0.25} />
                                <stop offset="100%" stopColor="var(--chart-1)" stopOpacity={0} />
                            </linearGradient>
                        </defs>
                        <CartesianGrid vertical={false} stroke="var(--border)" strokeDasharray="3 3" />
                        <XAxis dataKey="date" tickFormatter={formatDay} stroke="var(--muted-foreground)" fontSize={12} tickLine={false} axisLine={false} minTickGap={24} />
                        <YAxis allowDecimals={false} stroke="var(--muted-foreground)" fontSize={12} tickLine={false} axisLine={false} />
                        <Tooltip
                            labelFormatter={(value: string) => formatDay(value)}
                            formatter={(value: number, key: string) => [`${value} case(s)`, key === 'cumulative' ? 'Cumulative' : 'Created that day']}
                            contentStyle={{ background: 'var(--popover)', borderColor: 'var(--border)', borderRadius: 8, fontSize: 12 }}
                        />
                        <Area type="monotone" dataKey="cumulative" stroke="var(--chart-1)" strokeWidth={2} fill="url(#creationTrendFill)" activeDot={{ r: 4 }} />
                    </AreaChart>
                </ResponsiveContainer>
            </CardContent>
        </Card>
    );
}