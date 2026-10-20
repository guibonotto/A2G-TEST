import { Slot } from "@radix-ui/react-slot"
import { cva, type VariantProps } from "class-variance-authority"
import * as React from "react"

import { cn } from "@/lib/utils"

const badgeVariants = cva(
  "inline-flex items-center justify-center rounded-md border px-2 py-0.5 text-xs font-medium w-fit whitespace-nowrap shrink-0 [&>svg]:size-3 gap-1 [&>svg]:pointer-events-none focus-visible:border-ring focus-visible:ring-ring/50 focus-visible:ring-[3px] aria-invalid:ring-destructive/20 dark:aria-invalid:ring-destructive/40 aria-invalid:border-destructive transition-[color,box-shadow] overflow-hidden",
  {
    variants: {
      variant: {
        default:
          "border-transparent bg-primary text-primary-foreground [a&]:hover:bg-primary/90",
        secondary:
          "border-transparent bg-secondary text-secondary-foreground [a&]:hover:bg-secondary/90",
        // Light fill like success/warning/info: a solid red badge read as a
        // button sitting next to the real Delete button.
        destructive:
          "border-destructive/25 bg-destructive/12 text-destructive [a&]:hover:bg-destructive/20 focus-visible:ring-destructive/20",
        outline:
          "text-foreground [a&]:hover:bg-accent [a&]:hover:text-accent-foreground",
        success:
          "border-success/25 bg-success/12 text-success [a&]:hover:bg-success/20",
        warning:
          "border-warning/25 bg-warning/12 text-warning [a&]:hover:bg-warning/20",
        info:
          "border-info/25 bg-info/12 text-info [a&]:hover:bg-info/20",
        blue:
          "border-blue-500/25 bg-blue-500/12 text-blue-600 dark:text-blue-400",
        purple:
          "border-violet-500/25 bg-violet-500/12 text-violet-600 dark:text-violet-400",
        orange:
          "border-orange-500/25 bg-orange-500/12 text-orange-600 dark:text-orange-400",
        pink:
          "border-pink-500/25 bg-pink-500/12 text-pink-600 dark:text-pink-400",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
)

function Badge({
  className,
  variant,
  asChild = false,
  ...props
}: React.ComponentProps<"span"> &
  VariantProps<typeof badgeVariants> & { asChild?: boolean }) {
  const Comp = asChild ? Slot : "span"

  return (
    <Comp
      data-slot="badge"
      className={cn(badgeVariants({ variant }), className)}
      {...props}
    />
  )
}

export { Badge, badgeVariants }
