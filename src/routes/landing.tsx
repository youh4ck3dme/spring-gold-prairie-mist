import { createFileRoute } from "@tanstack/react-router";
import { LandingPresentation } from "@/components/landing-presentation";

export const Route = createFileRoute("/landing")({
  component: LandingPresentation,
});
