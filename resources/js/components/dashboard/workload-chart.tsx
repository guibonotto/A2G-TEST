import { Bar, BarChart, CartesianGrid, ResponsiveContainer, Tooltip, XAxis, YAxis } from 'recharts';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import type { WorkloadItem } from '@/types/dashboard';

export function WorkloadChart({ data }: { data: WorkloadItem[] }) {
    return (
        <Card>
            <CardHeader>
                <CardTitle>Workload</CardTitle>
                <CardDescription>Top assignees by number of assigned test cases</CardDescription>
            </CardHeader>
            <CardContent>
                {data.length === 0 ? (
                    <p className="py-12 text-center text-sm text-muted-foreground">No test cases registered yet.</p>
                ) : (
                    <ResponsiveContainer width="100%" height={Math.max(160, data.length * 40)}>
                        <BarChart data={data} layout="vertical" margin={{ left: 8, right: 16 }}>
                            <CartesianGrid horizontal={false} stroke="var(--border)" strokeDasharray="3 3" />
                            <XAxis type="number" allowDecimals={false} stroke="var(--muted-foreground)" fontSize={12} />
                            <YAxis type="category" dataKey="name" width={120} stroke="var(--muted-foreground)" fontSize={12} tickLine={false} axisLine={false} />
                            <Tooltip
                                cursor={{ fill: 'var(--muted)' }}
                                formatter={(value: number) => [`${value} case(s)`, 'Assigned']}
                                contentStyle={{ background: 'var(--popover)', borderColor: 'var(--border)', borderRadius: 8, fontSize: 12 }}
                            />
                            <Bar dataKey="total" radius={[0, 4, 4, 0]} barSize={18} fill="var(--chart-1)" />
                        </BarChart>
                    </ResponsiveContainer>
                )}
            </CardContent>
        </Card>
    );
}