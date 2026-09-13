import { Plus, Trash2 } from 'lucide-react';
import Heading from '@/components/heading';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Label } from '@/components/ui/label';
import { Textarea } from '@/components/ui/textarea';

export type StepFormValue = {
    description: string;
    expected_result: string;
};

export const emptyStep: StepFormValue = {
    description: '',
    expected_result: '',
};

type Props = {
    steps: StepFormValue[];
    onChange: (steps: StepFormValue[]) => void;
    /** Validation errors keyed by `steps` (list level) or `steps.{index}.{field}`. */
    errors?: Record<string, string | undefined>;
    /** Prefix for input ids, so several editors can live on the same page. */
    idPrefix?: string;
    description?: string;
    disabled?: boolean;
};

/**
 * Ordered list of steps (action + expected result) with add/remove controls.
 * Shared by the test case and test template forms.
 */
export default function StepsEditor({
    steps,
    onChange,
    errors = {},
    idPrefix = 'step',
    description = 'Add at least one step.',
    disabled = false,
}: Props) {
    function updateStep(
        stepIndex: number,
        field: keyof StepFormValue,
        value: string,
    ) {
        onChange(
            steps.map((step, i) =>
                i === stepIndex ? { ...step, [field]: value } : step,
            ),
        );
    }

    function addStep() {
        onChange([...steps, { ...emptyStep }]);
    }

    function removeStep(stepIndex: number) {
        onChange(steps.filter((_, i) => i !== stepIndex));
    }

    return (
        <div className="flex flex-col gap-4">
            <div className="flex items-center justify-between">
                <Heading
                    variant="small"
                    title="Steps"
                    description={description}
                />

                <Button
                    type="button"
                    variant="outline"
                    size="sm"
                    onClick={addStep}
                    disabled={disabled}
                >
                    <Plus /> Add step
                </Button>
            </div>

            <InputError message={errors.steps} />

            {steps.map((step, stepIndex) => (
                <div
                    key={stepIndex}
                    className="flex flex-col gap-3 rounded-lg border p-4"
                >
                    <div className="flex items-center justify-between">
                        <span className="text-sm font-medium">
                            Step {stepIndex + 1}
                        </span>

                        {steps.length > 1 && (
                            <Button
                                type="button"
                                variant="ghost"
                                size="icon"
                                onClick={() => removeStep(stepIndex)}
                                disabled={disabled}
                                aria-label={`Remove step ${stepIndex + 1}`}
                            >
                                <Trash2 />
                            </Button>
                        )}
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`${idPrefix}-description-${stepIndex}`}>
                            Action
                        </Label>
                        <Textarea
                            id={`${idPrefix}-description-${stepIndex}`}
                            value={step.description}
                            onChange={(e) =>
                                updateStep(
                                    stepIndex,
                                    'description',
                                    e.target.value,
                                )
                            }
                            disabled={disabled}
                        />
                        <InputError
                            message={errors[`steps.${stepIndex}.description`]}
                        />
                    </div>

                    <div className="grid gap-2">
                        <Label htmlFor={`${idPrefix}-expected-${stepIndex}`}>
                            Expected result
                        </Label>
                        <Textarea
                            id={`${idPrefix}-expected-${stepIndex}`}
                            value={step.expected_result}
                            onChange={(e) =>
                                updateStep(
                                    stepIndex,
                                    'expected_result',
                                    e.target.value,
                                )
                            }
                            disabled={disabled}
                        />
                        <InputError
                            message={
                                errors[`steps.${stepIndex}.expected_result`]
                            }
                        />
                    </div>
                </div>
            ))}
        </div>
    );
}
