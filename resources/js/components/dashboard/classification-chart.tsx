import {
    Bar,
    BarChart,
    CartesianGrid,
    Cell,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';
import { ChartEmptyState } from '@/components/dashboard/chart-empty-state';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import type { ClassificationBreakdownItem } from '@/types/dashboard';

const SERIES_COLORS = [
    'var(--chart-1)',
    'var(--chart-2)',
    'var(--chart-3)',
    'var(--chart-4)',
    'var(--chart-5)',
];

export function ClassificationChart({
    data,
}: {
    data: ClassificationBreakdownItem[];
}) {
    const plotted = data.filter((item) => item.total > 0);

    return (
        <Card>
            <CardHeader>
                <CardTitle>Cases by Classification</CardTitle>
                <CardDescription>
                    Unit vs. integration and any other registered
                    classifications
                </CardDescription>
            </CardHeader>
            <CardContent>
                {plotted.length === 0 ? (
                    <ChartEmptyState />
                ) : (
                    <ResponsiveContainer
                        width="100%"
                        height={Math.max(120, plotted.length * 44)}
                    >
                        <BarChart
                            data={plotted}
                            layout="vertical"
                            margin={{ left: 8, right: 16 }}
                        >
                            <CartesianGrid
                                horizontal={false}
                                stroke="var(--border)"
                                strokeDasharray="3 3"
                            />
                            <XAxis
                                type="number"
                                allowDecimals={false}
                                stroke="var(--muted-foreground)"
                                fontSize={12}
                            />
                            <YAxis
                                type="category"
                                dataKey="name"
                                width={110}
                                stroke="var(--muted-foreground)"
                                fontSize={12}
                                tickLine={false}
                                axisLine={false}
                            />
                            <Tooltip
                                cursor={{ fill: 'var(--muted)' }}
                                formatter={(value: number) => [
                                    `${value} case(s)`,
                                    'Total',
                                ]}
                                contentStyle={{
                                    background: 'var(--popover)',
                                    borderColor: 'var(--border)',
                                    borderRadius: 8,
                                    fontSize: 12,
                                }}
                            />
                            <Bar
                                dataKey="total"
                                radius={[0, 4, 4, 0]}
                                barSize={20}
                            >
                                {plotted.map((item, index) => (
                                    <Cell
                                        key={item.id}
                                        fill={
                                            SERIES_COLORS[
                                                index % SERIES_COLORS.length
                                            ]
                                        }
                                    />
                                ))}
                            </Bar>
                        </BarChart>
                    </ResponsiveContainer>
                )}
            </CardContent>
        </Card>
    );
}
