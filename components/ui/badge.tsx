import { cn } from "@/lib/utils";
import { cva, type VariantProps } from "class-variance-authority";
import { type HTMLAttributes } from "react";

const badgeVariants = cva(
  "inline-flex items-center rounded-full px-3 py-1 text-xs font-bold tracking-wide transition-colors",
  {
    variants: {
      variant: {
        default: "bg-[#f1f5f9] text-[#64748b]",
        success: "bg-[#ecfdf5] text-[#059669] ring-1 ring-inset ring-[#059669]/20",
        warning: "bg-[#fffbeb] text-[#d97706] ring-1 ring-inset ring-[#d97706]/20",
        danger: "bg-[#fef2f2] text-[#dc2626] ring-1 ring-inset ring-[#dc2626]/20",
        primary: "bg-[#c7eafb] text-[#004071]",
      },
    },
    defaultVariants: {
      variant: "default",
    },
  }
);

interface BadgeProps
  extends HTMLAttributes<HTMLDivElement>,
    VariantProps<typeof badgeVariants> {}

function Badge({ className, variant, ...props }: BadgeProps) {
  return (
    <div className={cn(badgeVariants({ variant }), className)} {...props} />
  );
}

export { Badge, badgeVariants };
