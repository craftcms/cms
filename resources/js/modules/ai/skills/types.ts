/** A skill as the AI Guidance & Skills utility lists it. */
export interface AgentSkillData {
  id: number;
  title: string;
  /** What the skill does and when to use it, which the assistant chooses skills by. */
  description: string;
  body: string;
  /** The skill’s screen, which the utility opens in a slideout. */
  editUrl: string;
}
