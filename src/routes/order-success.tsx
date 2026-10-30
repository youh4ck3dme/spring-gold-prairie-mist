import { createFileRoute } from "@tanstack/react-router";
import { OrderSuccessView } from "@/components/order-success";

export const Route = createFileRoute("/order-success")({
  component: OrderSuccessView,
});
