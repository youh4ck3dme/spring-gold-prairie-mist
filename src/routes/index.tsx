import { createFileRoute } from "@tanstack/react-router";
import { VypisApp } from "@/components/vypis-app";

export const Route = createFileRoute("/")({ component: VypisApp });
